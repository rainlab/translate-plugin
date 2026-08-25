<?php namespace RainLab\Translate\Tests\Unit\Console;

use File;
use PluginTestCase;
use October\Rain\Halcyon\Model;
use October\Rain\Filesystem\Filesystem;
use October\Rain\Halcyon\Datasource\FileDatasource;
use October\Rain\Halcyon\Datasource\Resolver;
use RainLab\Translate\Console\ImportThemeCommand;
use RainLab\Translate\Tests\Fixtures\Classes\TranslatablePage;
use ReflectionMethod;

class ImportThemeCommandTest extends PluginTestCase
{
    public $themePath;

    public function setUp(): void
    {
        parent::setUp();

        $this->themePath = __DIR__ . '/../../fixtures/themes/test';

        $datasource = new FileDatasource($this->themePath, new Filesystem);
        $resolver = new Resolver(['theme1' => $datasource]);
        $resolver->setDefaultDatasource('theme1');
        Model::setDatasourceResolver($resolver);
    }

    public function tearDown(): void
    {
        File::deleteDirectory($this->themePath.'/pages');

        parent::tearDown();
    }

    public function testMigratePageMovesViewBagKeys()
    {
        $page = TranslatablePage::create([
            'fileName' => 'legacy',
            'title' => 'Legacy',
            'url' => '/legacy',
        ]);

        array_set($page->attributes, 'viewBag.localeUrl.fr', '/patrimoine');
        array_set($page->attributes, 'viewBag.localeTitle.fr', 'Patrimoine');
        array_set($page->attributes, 'viewBag.localeMeta_title.fr', 'Meta Patrimoine');
        array_set($page->attributes, 'viewBag.someOther', 'keep-me');
        $page->save();

        $this->runMigratePage($page);

        $this->assertEquals('/patrimoine', array_get($page->attributes, 'translatable.locales.fr.url'));
        $this->assertEquals('Patrimoine', array_get($page->attributes, 'translatable.locales.fr.title'));
        $this->assertEquals('Meta Patrimoine', array_get($page->attributes, 'translatable.locales.fr.meta_title'));
        $this->assertNull(array_get($page->attributes, 'viewBag.localeUrl'));
        $this->assertEquals('keep-me', array_get($page->attributes, 'viewBag.someOther'));

        $contents = file_get_contents($this->themePath.'/pages/legacy.htm');
        $this->assertStringContainsString('locales[fr][url] = "/patrimoine"', $contents);
        $this->assertStringContainsString('someOther = "keep-me"', $contents);
        $this->assertStringNotContainsString('localeUrl', $contents);
    }

    public function testMigratePageRemovesEmptyViewBag()
    {
        $page = TranslatablePage::create([
            'fileName' => 'legacy2',
            'title' => 'Legacy Two',
            'url' => '/legacy-two',
        ]);

        array_set($page->attributes, 'viewBag.localeUrl.fr', '/ancien');
        $page->save();

        $this->runMigratePage($page);

        $this->assertNull(array_get($page->attributes, 'viewBag'));

        $contents = file_get_contents($this->themePath.'/pages/legacy2.htm');
        $this->assertStringNotContainsString('[viewBag]', $contents);
        $this->assertStringContainsString('locales[fr][url] = "/ancien"', $contents);
    }

    public function testMigratePageKeepsExistingTranslatableValues()
    {
        $page = TranslatablePage::create([
            'fileName' => 'legacy3',
            'title' => 'Legacy Three',
            'url' => '/legacy-three',
        ]);

        array_set($page->attributes, 'translatable.locales.fr.url', '/existant');
        array_set($page->attributes, 'viewBag.localeUrl.fr', '/ancien');
        $page->save();

        $this->runMigratePage($page);

        $this->assertEquals('/existant', array_get($page->attributes, 'translatable.locales.fr.url'));
        $this->assertNull(array_get($page->attributes, 'viewBag'));
    }

    /**
     * runMigratePage invokes the protected migration method on the command.
     */
    protected function runMigratePage($page): void
    {
        $command = new ImportThemeCommand;
        $method = new ReflectionMethod($command, 'migratePage');
        $method->setAccessible(true);
        $method->invoke($command, $page);
    }
}
