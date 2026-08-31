<?php namespace RainLab\Translate\Console;

use Cms\Classes\Page;
use Cms\Classes\Theme;
use Cms\Classes\Content;
use RainLab\Translate\Classes\Locale;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * ImportThemeCommand migrates translated theme files from RainLab.Translate
 * conventions to the core translation features.
 *
 * Converts [viewBag] localeUrl, localeTitle, localeDescription, localeMeta_title and
 * localeMeta_description keys into [translatable] locales entries, and moves content
 * files using the locale suffix (welcome.fr.htm) into locale directories (fr/welcome.htm),
 * used by October CMS v4.4 or above.
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
    protected $description = 'Migrates translated pages and content files to the core translation features';

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

        $pages = [];
        foreach (Page::listInTheme($theme, true) as $page) {
            if ($this->pageHasLegacyKeys($page)) {
                $pages[] = $page;
            }
        }

        $localeCodes = array_keys(Locale::listAvailable());
        $contents = $this->findContentCandidates($theme, $localeCodes);

        if (!count($pages) && !count($contents)) {
            $this->info("No pages or content files to migrate in theme [{$theme->getDirName()}].");
            return 0;
        }

        if (count($pages)) {
            $this->info(sprintf('Found %d page(s) to migrate in theme [%s].', count($pages), $theme->getDirName()));

            foreach ($pages as $page) {
                $this->line(' - '.$page->getFileName());
            }
        }

        if (count($contents)) {
            $this->info(sprintf('Found %d content file(s) to move in theme [%s].', count($contents), $theme->getDirName()));

            foreach ($contents as $item) {
                $this->line(sprintf(' - %s -> %s', $item['content']->getFileName(), $item['target']));
            }
        }

        if (!$this->option('force') && !$this->confirm('Proceed with migration?')) {
            return 0;
        }

        foreach ($pages as $page) {
            $this->migratePage($page);
            $this->info('Migrated: '.$page->getFileName());
        }

        foreach ($contents as $item) {
            if ($this->migrateContent($item['content'], $item['target'])) {
                $this->info('Moved: '.$item['target']);
            }
            else {
                $this->warn('Skipped (target already exists): '.$item['target']);
            }
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
     * findContentCandidates locates content files using the locale suffix convention.
     */
    protected function findContentCandidates($theme, array $localeCodes): array
    {
        $result = [];

        foreach (Content::listInTheme($theme, true) as $content) {
            $target = $this->makeContentTargetName($content->getFileName(), $localeCodes);
            if ($target !== null) {
                $result[] = ['content' => $content, 'target' => $target];
            }
        }

        return $result;
    }

    /**
     * makeContentTargetName converts a suffixed file name (blog/intro.fr.htm) to a locale
     * directory path (fr/blog/intro.htm), returning null when no locale suffix matches.
     */
    protected function makeContentTargetName(string $fileName, array $localeCodes): ?string
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        if (!$extension) {
            return null;
        }

        $baseName = substr($fileName, 0, -(strlen($extension) + 1));

        foreach ($localeCodes as $locale) {
            if (str_ends_with($baseName, '.'.$locale)) {
                $stripped = substr($baseName, 0, -(strlen($locale) + 1));
                return "{$locale}/{$stripped}.{$extension}";
            }
        }

        return null;
    }

    /**
     * migrateContent renames a content file into its locale directory, returning false
     * when the target already exists.
     */
    protected function migrateContent($content, string $target): bool
    {
        if ($content->newQuery()->find($target)) {
            return false;
        }

        $content->fileName = $target;
        $content->save();

        return true;
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
