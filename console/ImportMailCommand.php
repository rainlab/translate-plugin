<?php namespace RainLab\Translate\Console;

use View;
use System\Models\MailTemplate;
use System\Classes\MailManager;
use RainLab\Translate\Classes\Locale;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * ImportMailCommand migrates mail templates using the RainLab.Translate locale
 * suffix convention (welcome-fr) to translated attributes on the base template,
 * used by the core Translatable trait in October CMS v4.4 or above.
 */
class ImportMailCommand extends Command
{
    /**
     * @var string name
     */
    protected $name = 'translate:import-mail';

    /**
     * @var string description
     */
    protected $description = 'Migrates locale suffix mail templates to core translated attributes';

    /**
     * @var array translatableAttributes copied from the suffix record.
     */
    protected $translatableAttributes = [
        'subject',
        'content_html',
        'content_text',
    ];

    /**
     * handle
     */
    public function handle()
    {
        if (!method_exists(MailTemplate::class, 'setTranslation')) {
            $this->error('The core Translatable trait was not found on mail templates. October CMS v4.4 or above is required.');
            return 1;
        }

        $localeCodes = array_keys(Locale::listAvailable());

        $candidates = $this->findSuffixCandidates($localeCodes);

        $this->reportSuffixViews($localeCodes);

        if (!count($candidates)) {
            $this->info('No mail templates with locale suffixes found in the database.');
            return 0;
        }

        $this->info(sprintf('Found %d mail template(s) to migrate.', count($candidates)));

        foreach ($candidates as $item) {
            $this->line(sprintf(' - %s -> %s [%s]', $item['record']->code, $item['base'], $item['locale']));
        }

        if (!$this->option('force') && !$this->confirm('Proceed with migration? Suffix records are removed after migrating.')) {
            return 0;
        }

        foreach ($candidates as $item) {
            if ($this->migrateTemplate($item['record'], $item['base'], $item['locale'])) {
                $this->info('Migrated: '.$item['record']->code);
            }
            else {
                $this->warn('Skipped (base template not found): '.$item['record']->code);
            }
        }

        $this->newLine();
        $this->info('Migration complete. Existing translated values were kept where already set.');

        return 0;
    }

    /**
     * findSuffixCandidates locates database templates using the locale suffix convention.
     */
    protected function findSuffixCandidates(array $localeCodes): array
    {
        $result = [];

        foreach (MailTemplate::all() as $record) {
            $target = $this->makeSuffixTarget($record->code, $localeCodes);
            if ($target !== null) {
                $result[] = ['record' => $record, 'base' => $target[0], 'locale' => $target[1]];
            }
        }

        return $result;
    }

    /**
     * makeSuffixTarget returns the base code and locale for a suffixed template code
     * (welcome-fr gives [welcome, fr]), or null when no locale suffix matches.
     */
    protected function makeSuffixTarget(string $code, array $localeCodes): ?array
    {
        // Longest first so regional suffixes match before their base language
        usort($localeCodes, fn($a, $b) => strlen($b) <=> strlen($a));

        foreach ($localeCodes as $locale) {
            $suffix = '-'.strtolower($locale);
            if (str_ends_with(strtolower($code), $suffix) && strlen($code) > strlen($suffix)) {
                return [substr($code, 0, -strlen($suffix)), strtolower($locale)];
            }
        }

        return null;
    }

    /**
     * migrateTemplate copies translatable values from a suffix record to the base
     * template and removes the suffix record, returning false when no base exists.
     */
    protected function migrateTemplate($record, string $baseCode, string $locale): bool
    {
        if (!$base = MailTemplate::findOrMakeTemplate($baseCode)) {
            return false;
        }

        if (!$base->exists) {
            $base->description = $record->description ?: $base->subject;
            $base->is_custom = 0;
            $base->forceSave();
        }

        foreach ($this->translatableAttributes as $attribute) {
            $value = $record->getAttribute($attribute);
            if ($value === null || $value === '') {
                continue;
            }

            // Keep any translation already stored on the base record
            $existing = $base->getTranslation($attribute, $locale, false);
            if ($existing === null || $existing === '') {
                $base->setTranslation($attribute, $locale, $value);
            }
        }

        $base->forceSave();

        $record->delete();

        return true;
    }

    /**
     * reportSuffixViews warns about registered view templates using the suffix
     * convention, which live in plugin code and cannot be moved automatically.
     */
    protected function reportSuffixViews(array $localeCodes): void
    {
        $registered = (array) MailManager::instance()->listRegisteredTemplates();

        foreach ($registered as $code => $view) {
            if ($this->makeSuffixTarget($code, $localeCodes) !== null && View::exists($view)) {
                $this->warn(sprintf(
                    'Registered view template [%s] uses a locale suffix; move the view into a locale directory instead (welcome-fr becomes fr/welcome).',
                    $code
                ));
            }
        }
    }

    /**
     * getOptions
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Skip confirmation prompts'],
        ];
    }
}
