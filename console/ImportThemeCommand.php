<?php namespace RainLab\Translate\Console;

use Cms\Classes\Page;
use Cms\Classes\Theme;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * ImportThemeCommand migrates translated page properties from RainLab.Translate
 * viewBag keys to the core translatable component section.
 *
 * Converts [viewBag] localeUrl, localeTitle, localeDescription, localeMeta_title and
 * localeMeta_description keys into [translatable] locales entries, used by
 * October CMS v4.4 or above.
 */
class ImportThemeCommand extends Command
{
    /**
     * @var string name
     */
    protected $name = 'translate:import-theme';

    /**
     * @var string description
     */
    protected $description = 'Migrates translated page properties from viewBag keys to the core translatable component';

    /**
     * @var array propertyMap of viewBag keys to translatable fields.
     */
    protected $propertyMap = [
        'localeUrl' => 'url',
        'localeTitle' => 'title',
        'localeDescription' => 'description',
        'localeMeta_title' => 'meta_title',
        'localeMeta_description' => 'meta_description',
    ];

    /**
     * handle
     */
    public function handle()
    {
        if (!class_exists(\Cms\Components\TranslatableBag::class)) {
            $this->error('The translatable component was not found. October CMS v4.4 or above is required.');
            return 1;
        }

        if ($themeName = $this->option('theme')) {
            if (!Theme::exists($themeName)) {
                $this->error("Theme [{$themeName}] not found.");
                return 1;
            }
            $theme = Theme::load($themeName);
        }
        else {
            $theme = Theme::getActiveTheme();
        }

        if (!$theme) {
            $this->error('No active theme found.');
            return 1;
        }

        $candidates = [];
        foreach (Page::listInTheme($theme, true) as $page) {
            if ($this->pageHasLegacyKeys($page)) {
                $candidates[] = $page;
            }
        }

        if (!count($candidates)) {
            $this->info("No pages with translated viewBag properties found in theme [{$theme->getDirName()}].");
            return 0;
        }

        $this->info(sprintf('Found %d page(s) to migrate in theme [%s].', count($candidates), $theme->getDirName()));

        foreach ($candidates as $page) {
            $this->line(' - '.$page->getFileName());
        }

        if (!$this->option('force') && !$this->confirm('Proceed with migration?')) {
            return 0;
        }

        foreach ($candidates as $page) {
            $this->migratePage($page);
            $this->info('Migrated: '.$page->getFileName());
        }

        $this->newLine();
        $this->info('Migration complete. Existing [translatable] values were kept where already set.');

        return 0;
    }

    /**
     * pageHasLegacyKeys checks for any translated viewBag properties on a page.
     */
    protected function pageHasLegacyKeys($page): bool
    {
        foreach ($this->propertyMap as $legacyKey => $field) {
            if (is_array(array_get($page->attributes, 'viewBag.'.$legacyKey))) {
                return true;
            }
        }

        return false;
    }

    /**
     * migratePage moves viewBag locale keys into the translatable section and saves the page.
     */
    protected function migratePage($page): void
    {
        foreach ($this->propertyMap as $legacyKey => $field) {
            $values = array_get($page->attributes, 'viewBag.'.$legacyKey);
            if (!is_array($values)) {
                continue;
            }

            foreach ($values as $locale => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $target = "translatable.locales.{$locale}.{$field}";
                if (array_get($page->attributes, $target) === null) {
                    array_set($page->attributes, $target, $value);
                }
            }

            array_forget($page->attributes, 'viewBag.'.$legacyKey);
        }

        if (empty(array_get($page->attributes, 'viewBag'))) {
            array_forget($page->attributes, 'viewBag');
        }

        $page->save();
    }

    /**
     * getOptions
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Skip confirmation prompts'],
            ['theme', null, InputOption::VALUE_REQUIRED, 'Migrate a specific theme instead of the active theme'],
        ];
    }
}
