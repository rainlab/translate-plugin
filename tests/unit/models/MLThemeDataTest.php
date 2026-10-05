<?php namespace RainLab\Translate\Tests\Unit\Models;

use Db;
use Site;
use PluginTestCase;
use Cms\Classes\Theme;
use Cms\Models\ThemeData;
use Backend\Widgets\Form;
use Backend\Classes\Controller;
use RainLab\Translate\Models\MLThemeData;

/**
 * MLThemeDataTest covers which theme data model is used and translating through the plugin model
 */
class MLThemeDataTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->app->useThemesPath(__DIR__ . '/../../fixtures/themes');
        Theme::resetCache();
        MLThemeData::clearStoredDataCache();
    }

    public function tearDown(): void
    {
        MLThemeData::clearStoredDataCache();
        Site::resetCache();

        parent::tearDown();
    }

    /**
     * testCoreModelWithoutPluginData confirms the core model is used when the plugin holds no theme data
     */
    public function testCoreModelWithoutPluginData()
    {
        $this->assertSame(ThemeData::class, get_class(ThemeData::createThemeDataModel()));
    }

    /**
     * testPluginModelWithPluginData confirms the plugin model is kept while the plugin holds theme data
     */
    public function testPluginModelWithPluginData()
    {
        $themeDataId = $this->insertThemeData();
        $this->insertPluginTranslation($themeDataId, 'fr', ['title' => 'Ancien titre']);

        $this->assertInstanceOf(MLThemeData::class, ThemeData::createThemeDataModel());
    }

    /**
     * testPluginModelWithPluginFiles confirms the plugin model is kept while theme files are attached to it
     */
    public function testPluginModelWithPluginFiles()
    {
        $themeDataId = $this->insertThemeData();

        Db::table('system_files')->insert([
            'disk_name' => uniqid() . '.jpg',
            'file_name' => 'theme-data-logo.jpg',
            'file_size' => 0,
            'content_type' => 'image/jpeg',
            'attachment_type' => MLThemeData::class,
            'attachment_id' => $themeDataId,
            'field' => 'logo',
            'is_public' => true
        ]);

        $this->assertInstanceOf(MLThemeData::class, ThemeData::createThemeDataModel());
    }

    /**
     * testPluginModelSkipsCoreTranslations confirms the plugin model reads its own translations without querying the core table
     */
    public function testPluginModelSkipsCoreTranslations()
    {
        $site = $this->makeSite('fr');
        $themeDataId = $this->insertThemeData();
        $this->insertPluginTranslation($themeDataId, 'fr', ['title' => 'Ancien titre']);

        Site::withContext($site->id, function () {
            Db::enableQueryLog();
            $model = MLThemeData::where('theme', 'themedata')->first();
            $model->lang('fr');
            $title = $model->title;
            $queries = implode(' ', array_column(Db::getQueryLog(), 'query'));
            Db::disableQueryLog();

            $this->assertEquals('Ancien titre', $title);
            $this->assertStringNotContainsString('system_translate_attributes', $queries);
        });
    }

    /**
     * testTranslatePopupSavesPluginTranslation confirms the core translate popup still saves through the plugin model
     */
    public function testTranslatePopupSavesPluginTranslation()
    {
        $site = $this->makeSite('fr');
        $themeDataId = $this->insertThemeData();
        $this->insertPluginTranslation($themeDataId, 'fr', ['title' => 'Ancien titre']);

        $this->swapRequest([
            'field_name' => 'title',
            'site_id' => $site->id,
            'TranslateField' => ['title' => 'Titre']
        ], 'form::onSaveTranslateField');

        $form = $this->makeForm(MLThemeData::where('theme', 'themedata')->first());
        $form->onSaveTranslateField();

        $data = json_decode(Db::table('cms_theme_data')->where('id', $themeDataId)->value('data'), true);
        $this->assertEquals('Base title', $data['title']);

        $translation = Db::table('rainlab_translate_attributes')
            ->where('model_type', MLThemeData::class)
            ->where('locale', 'fr')
            ->value('attribute_data');

        $this->assertEquals('Titre', json_decode($translation, true)['title']);
        $this->assertSame(0, Db::table('system_translate_attributes')->count());
    }

    /**
     * insertThemeData adds a theme data record with a default title
     */
    protected function insertThemeData(): int
    {
        return Db::table('cms_theme_data')->insertGetId([
            'theme' => 'themedata',
            'data' => json_encode(['title' => 'Base title'])
        ]);
    }

    /**
     * insertPluginTranslation adds a plugin translation record for the theme data
     */
    protected function insertPluginTranslation(int $themeDataId, string $locale, array $data)
    {
        Db::table('rainlab_translate_attributes')->insert([
            'model_type' => MLThemeData::class,
            'model_id' => (string) $themeDataId,
            'locale' => $locale,
            'attribute_data' => json_encode($data)
        ]);
    }

    /**
     * makeForm builds a form using the theme's own field definitions
     */
    protected function makeForm($model)
    {
        $fields = Theme::load('themedata')->getFormConfig()['fields'];

        $form = new Form(new Controller, [
            'model' => $model,
            'arrayName' => 'ThemeData',
            'fields' => array_only($fields, ['title'])
        ]);

        $form->bindToController();
        self::callProtectedMethod($form, 'defineFormFields');

        return $form;
    }

    /**
     * makeSite creates an edit enabled site for a locale
     */
    protected function makeSite(string $locale)
    {
        $site = new \System\Models\SiteDefinition;
        $site->name = 'Site ' . $locale;
        $site->code = 'site-' . $locale;
        $site->locale = $locale;
        $site->is_enabled = true;
        $site->is_enabled_edit = true;
        $site->save();

        Site::resetCache();

        return $site;
    }

    /**
     * swapRequest replaces the request with an AJAX handler request
     */
    protected function swapRequest(array $data, string $handler)
    {
        $request = \Illuminate\Http\Request::create('/', 'POST', $data, [], [], [
            'HTTP_X_AJAX_HANDLER' => $handler,
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ]);
        $this->app->instance('request', $request);
        \Illuminate\Support\Facades\Facade::clearResolvedInstance('request');
    }
}
