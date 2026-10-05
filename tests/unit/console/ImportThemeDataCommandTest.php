<?php namespace RainLab\Translate\Tests\Unit\Console;

use Db;
use Artisan;
use PluginTestCase;
use Cms\Classes\Theme;
use Cms\Models\ThemeData;
use RainLab\Translate\Models\MLThemeData;

/**
 * ImportThemeDataCommandTest covers moving theme data between the plugin and core theme data models
 */
class ImportThemeDataCommandTest extends PluginTestCase
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

        parent::tearDown();
    }

    /**
     * testImportMovesTranslationsAndFiles confirms translations and files move to the core model and the core takes over
     */
    public function testImportMovesTranslationsAndFiles()
    {
        $themeDataId = $this->insertThemeData();
        $this->insertPluginTranslation($themeDataId, 'fr', [
            'title' => 'Titre',
            'notes' => [['note' => 'Bonjour']]
        ]);
        $this->insertFile('theme-data-logo.jpg', MLThemeData::class, $themeDataId, 'logo');

        $this->assertTrue(MLThemeData::hasStoredData());
        $this->assertSame(0, Artisan::call('translate:import-theme-data', ['--force' => true]));

        $this->assertSame('Titre', $this->findCoreValue($themeDataId, 'title'));
        $this->assertSame('[{"note":"Bonjour"}]', $this->findCoreValue($themeDataId, 'notes'));
        $this->assertSame(0, Db::table('rainlab_translate_attributes')->count());
        $this->assertSame(ThemeData::class, Db::table('system_files')->where('file_name', 'theme-data-logo.jpg')->value('attachment_type'));

        $this->assertFalse(MLThemeData::hasStoredData());
        $this->assertSame(ThemeData::class, get_class(ThemeData::createThemeDataModel()));
    }

    /**
     * testRollbackMovesTranslationsAndFiles confirms core translations merge back into plugin records and files return to the plugin model
     */
    public function testRollbackMovesTranslationsAndFiles()
    {
        $themeDataId = $this->insertThemeData();
        $this->insertPluginTranslation($themeDataId, 'fr', ['subtitle' => 'Sous-titre']);
        $this->insertCoreValue($themeDataId, 'fr', 'title', 'Titre');
        $this->insertCoreValue($themeDataId, 'fr', 'notes', '[{"note":"Bonjour"}]');
        $this->insertFile('theme-data-logo.jpg', ThemeData::class, $themeDataId, 'logo');

        $this->assertSame(0, Artisan::call('translate:import-theme-data', ['--force' => true, '--rollback' => true]));

        $data = json_decode(Db::table('rainlab_translate_attributes')
            ->where('model_type', MLThemeData::class)
            ->where('locale', 'fr')
            ->value('attribute_data'), true);

        $this->assertEquals([
            'subtitle' => 'Sous-titre',
            'title' => 'Titre',
            'notes' => [['note' => 'Bonjour']]
        ], $data);

        $this->assertSame(0, Db::table('system_translate_attributes')->count());
        $this->assertSame(MLThemeData::class, Db::table('system_files')->where('file_name', 'theme-data-logo.jpg')->value('attachment_type'));

        $this->assertTrue(MLThemeData::hasStoredData());
        $this->assertInstanceOf(MLThemeData::class, ThemeData::createThemeDataModel());
    }

    /**
     * testImportWithoutThemeData confirms nothing changes when the plugin holds no theme data
     */
    public function testImportWithoutThemeData()
    {
        $this->assertSame(0, Artisan::call('translate:import-theme-data', ['--force' => true]));

        $this->assertStringContainsString('No theme data found to import.', Artisan::output());
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
     * insertCoreValue adds a core translation row for the theme data
     */
    protected function insertCoreValue(int $themeDataId, string $locale, string $attribute, string $value)
    {
        Db::table('system_translate_attributes')->insert([
            'model_type' => ThemeData::class,
            'model_id' => $themeDataId,
            'locale' => $locale,
            'attribute' => $attribute,
            'value' => $value
        ]);
    }

    /**
     * findCoreValue returns a core translation value for the theme data
     */
    protected function findCoreValue(int $themeDataId, string $attribute)
    {
        return Db::table('system_translate_attributes')
            ->where('model_type', ThemeData::class)
            ->where('model_id', $themeDataId)
            ->where('locale', 'fr')
            ->where('attribute', $attribute)
            ->value('value');
    }

    /**
     * insertFile adds a file attached to the theme data
     */
    protected function insertFile(string $name, string $type, $attachmentId, string $field)
    {
        Db::table('system_files')->insert([
            'disk_name' => uniqid() . '.jpg',
            'file_name' => $name,
            'file_size' => 0,
            'content_type' => 'image/jpeg',
            'attachment_type' => $type,
            'attachment_id' => $attachmentId,
            'field' => $field,
            'is_public' => true
        ]);
    }
}
