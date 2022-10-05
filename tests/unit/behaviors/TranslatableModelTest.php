<?php namespace RainLab\Translate\Tests\Unit\Behaviors;

use Model;
use Schema;
use PluginTestCase;
use RainLab\Translate\Classes\Translator;
use RainLab\Translate\Tests\Fixtures\Models\Country as CountryModel;
use RainLab\Translate\Classes\Locale as LocaleModel;
use October\Rain\Database\Relations\Relation;

class TranslatableModelTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->seedSampleTableAndData();
    }

    protected function seedSampleTableAndData()
    {
        if (Schema::hasTable('translate_test_countries')) {
            return;
        }

        Model::unguard();

        Schema::create('translate_test_countries', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->text('states')->nullable();
            $table->timestamps();
        });

        LocaleModel::firstOrCreate([
            'code' => 'fr',
            'name' => 'French',
            'is_enabled' => 1
        ]);

        $this->recycleSampleData();

        Model::reguard();
    }

    protected function recycleSampleData()
    {
        CountryModel::truncate();

        CountryModel::create([
            'name' => 'Australia',
            'code' => 'AU',
            'states' => ['NSW', 'ACT', 'QLD'],
        ]);
    }

    public function testGetTranslationValue()
    {
        $obj = CountryModel::first();

        $this->assertEquals('Australia', $obj->name);
        $this->assertEquals(['NSW', 'ACT', 'QLD'], $obj->states);

        $obj->translateContext('fr');

        $this->assertEquals('Australia', $obj->name);
    }

    public function testGetTranslationValueNoFallback()
    {
        $obj = CountryModel::first();

        $this->assertEquals('Australia', $obj->name);

        $obj->noFallbackLocale()->translateContext('fr');

        $this->assertEquals(null, $obj->name);
    }

    public function testSetTranslationValue()
    {
        $this->recycleSampleData();

        $obj = CountryModel::first();
        $obj->name = 'Aussie';
        $obj->states = ['VIC', 'SA', 'NT'];
        $obj->save();

        $obj->translateContext('fr');
        $obj->name = 'Australie';
        $obj->states = ['a', 'b', 'c'];
        $obj->save();

        $obj = CountryModel::first();
        $this->assertEquals('Aussie', $obj->name);
        $this->assertEquals(['VIC', 'SA', 'NT'], $obj->states);

        $obj->translateContext('fr');
        $this->assertEquals('Australie', $obj->name);
        $this->assertEquals(['a', 'b', 'c'], $obj->states);
    }

    public function testGetTranslationValueEagerLoading()
    {
        $this->recycleSampleData();

        $obj = CountryModel::first();
        $obj->translateContext('fr');
        $obj->name = 'Australie';
        $obj->states = ['a', 'b', 'c'];
        $obj->save();

        $objList = CountryModel::with([
          'translations'
        ])->get();

        $obj = $objList[0];
        $this->assertEquals('Australia', $obj->name);
        $this->assertEquals(['NSW', 'ACT', 'QLD'], $obj->states);

        $obj->translateContext('fr');
        $this->assertEquals('Australie', $obj->name);
        $this->assertEquals(['a', 'b', 'c'], $obj->states);
    }

    public function testTranslateWhere()
    {
        $this->recycleSampleData();

        $obj = CountryModel::first();

        $obj->translateContext('fr');
        $obj->name = 'Australie';
        $obj->save();

        $this->assertEquals(0, CountryModel::transWhere('name', 'Australie')->count());

        Translator::instance()->setLocale('fr');
        $this->assertEquals(1, CountryModel::transWhere('name', 'Australie')->count());

        Translator::instance()->setLocale('en');
    }

    public function testTranslateOrderBy()
    {
        $this->recycleSampleData();

        $obj = CountryModel::first();

        $obj->translateContext('fr');
        $obj->name = 'Australie';
        $obj->save();

        $obj = CountryModel::create([
            'name' => 'Germany',
            'code' => 'DE'
        ]);

        $obj->translateContext('fr');
        $obj->name = 'Allemagne';
        $obj->save();

        $res = CountryModel::transOrderBy('name')->get()->pluck('name');
        $this->assertEquals(['Australia', 'Germany'], $res->toArray());

        Translator::instance()->setLocale('fr');
        $res = CountryModel::transOrderBy('name')->get()->pluck('name');
        $this->assertEquals(['Allemagne', 'Australie'], $res->toArray());

        Translator::instance()->setLocale('en');
    }

    public function testGetTranslationValueEagerLoadingWithMorphMap()
    {
        Relation::morphMap([
            'morph.key' => CountryModel::class,
        ]);

        $this->recycleSampleData();

        $obj = CountryModel::first();
        $obj->translateContext('fr');
        $obj->name = 'Australie';
        $obj->states = ['a', 'b', 'c'];
        $obj->save();

        $objList = CountryModel::with([
          'translations'
        ])->get();

        $obj = $objList[0];
        $this->assertEquals('Australia', $obj->name);
        $this->assertEquals(['NSW', 'ACT', 'QLD'], $obj->states);

        $obj->translateContext('fr');
        $this->assertEquals('Australie', $obj->name);
        $this->assertEquals(['a', 'b', 'c'], $obj->states);
    }
}
