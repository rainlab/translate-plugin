<?php namespace RainLab\Translate\Tests\Unit\Console;

use Db;
use Artisan;
use PluginTestCase;

/**
 * ImportCoreCommandTest covers migrating attribute translations to the core table
 */
class ImportCoreCommandTest extends PluginTestCase
{
    /**
     * testImportKeepsModelType confirms model translations keep their model type
     */
    public function testImportKeepsModelType()
    {
        $this->insertAttributes('Acme\Blog\Models\Post', 'fr', ['title' => 'Bonjour']);

        $this->assertSame(0, Artisan::call('translate:import-attributes', ['--force' => true]));

        $this->assertSame('Bonjour', $this->findValue('Acme\Blog\Models\Post', 'title'));
    }

    /**
     * testImportLeavesThemeData confirms theme data is left for the theme data import
     */
    public function testImportLeavesThemeData()
    {
        $this->insertAttributes('RainLab\Translate\Models\MLThemeData', 'fr', ['website_name' => 'Mon site']);

        $this->assertSame(0, Artisan::call('translate:import-attributes', ['--force' => true]));

        $this->assertSame(0, Db::table('system_translate_attributes')->count());
        $this->assertSame(1, Db::table('rainlab_translate_attributes')->count());
        $this->assertStringContainsString('translate:import-theme-data', Artisan::output());
    }

    /**
     * insertAttributes adds a RainLab.Translate attribute row
     */
    protected function insertAttributes(string $modelType, string $locale, array $data)
    {
        Db::table('rainlab_translate_attributes')->insert([
            'locale' => $locale,
            'model_id' => 1,
            'model_type' => $modelType,
            'attribute_data' => json_encode($data)
        ]);
    }

    /**
     * findValue returns an imported translation value
     */
    protected function findValue(string $modelType, string $attribute)
    {
        return Db::table('system_translate_attributes')
            ->where('model_type', $modelType)
            ->where('model_id', 1)
            ->where('locale', 'fr')
            ->where('attribute', $attribute)
            ->value('value');
    }
}
