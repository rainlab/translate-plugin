<?php namespace RainLab\Translate\Console;

use Db;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use RainLab\Translate\Classes\Translator;
use Symfony\Component\Console\Input\InputOption;

/**
 * ImportAttachmentsCommand moves translated file attachments from the locale suffixed type (Post:fr) to the locale suffixed field (image:fr).
 */
class ImportAttachmentsCommand extends Command
{
    /**
     * @var string name
     */
    protected $name = 'translate:import-attachments';

    /**
     * @var string description
     */
    protected $description = 'Converts translated file attachments from RainLab.Translate to the core Translatable trait';

    /**
     * handle
     */
    public function handle()
    {
        $defaultLocale = $this->option('default') ?: Translator::instance()->getDefaultLocale();

        if (!$defaultLocale) {
            $this->error('Unable to resolve the default locale, specify it with --default.');
            return 1;
        }

        if ($this->option('rollback')) {
            return $this->rollbackAttachments($defaultLocale);
        }

        return $this->importAttachments($defaultLocale);
    }

    /**
     * importAttachments converts every locale suffixed attachment type to a locale suffixed field.
     */
    protected function importAttachments(string $defaultLocale): int
    {
        $updates = [];
        $groups = [];

        $this->newTranslatedFilesQuery()->chunkById(500, function ($rows) use ($defaultLocale, &$updates, &$groups) {
            foreach ($rows as $row) {
                [$type, $locale] = $this->splitLocaleSuffix($row->attachment_type);
                if (!$this->matchesModelOption($type)) {
                    continue;
                }

                $updates[$row->id] = [
                    'attachment_type' => $type,
                    'field' => $locale === $defaultLocale ? $row->field : $row->field . ':' . $locale
                ];

                $groups[$type][$row->attachment_id][$row->field] = true;
            }
        });

        if (!$updates) {
            $this->info('No translated attachments found to import.');
            return 0;
        }

        $hiddenIds = $this->findHiddenFileIds($groups);

        $this->info(sprintf('Found %s translated attachment(s) to import.', count($updates)));

        if ($hiddenIds) {
            $this->warn(sprintf('%s untranslated attachment(s) were hidden by RainLab.Translate and will be detached.', count($hiddenIds)));
        }

        if (!$this->option('force') && !$this->confirm('Proceed with import?')) {
            return 0;
        }

        // Detach before converting so the default locale files are not mistaken for hidden ones
        foreach (array_chunk($hiddenIds, 500) as $ids) {
            Db::table('system_files')->whereIn('id', $ids)->update([
                'attachment_id' => null,
                'attachment_type' => null,
                'field' => null
            ]);
        }

        $this->applyUpdates($updates);

        $this->info(sprintf('Imported %s attachment(s), detached %s hidden attachment(s).', count($updates), count($hiddenIds)));

        return 0;
    }

    /**
     * rollbackAttachments converts locale suffixed fields back to locale suffixed attachment types.
     */
    protected function rollbackAttachments(string $defaultLocale): int
    {
        $updates = [];

        foreach ($this->getTranslatableAttachmentRelations() as $type => $relationNames) {
            Db::table('system_files')
                ->where('attachment_type', $type)
                ->whereNotNull('field')
                ->select('id', 'field')
                ->chunkById(500, function ($rows) use ($type, $relationNames, $defaultLocale, &$updates) {
                    foreach ($rows as $row) {
                        [$relationName, $locale] = $this->splitLocaleSuffix($row->field);
                        if (!in_array($relationName, $relationNames)) {
                            continue;
                        }

                        $updates[$row->id] = [
                            'attachment_type' => $type . ':' . ($locale ?? $defaultLocale),
                            'field' => $relationName
                        ];
                    }
                });
        }

        if (!$updates) {
            $this->info('No translatable attachments found to roll back.');
            return 0;
        }

        $this->info(sprintf('Found %s attachment(s) to roll back.', count($updates)));

        if (!$this->option('force') && !$this->confirm('Proceed with rollback?')) {
            return 0;
        }

        $this->applyUpdates($updates);

        $this->info(sprintf('Rolled back %s attachment(s).', count($updates)));

        return 0;
    }

    /**
     * newTranslatedFilesQuery returns a query for attachments stored with a locale suffixed type.
     */
    protected function newTranslatedFilesQuery()
    {
        return Db::table('system_files')
            ->where('attachment_type', 'like', '%:%')
            ->whereNotNull('field')
            ->select('id', 'attachment_type', 'attachment_id', 'field');
    }

    /**
     * findHiddenFileIds returns plain attachments on translated relations, which RainLab.Translate never displayed.
     */
    protected function findHiddenFileIds(array $groups): array
    {
        $ids = [];

        foreach ($groups as $type => $records) {
            foreach (array_chunk(array_keys($records), 500) as $attachmentIds) {
                $rows = Db::table('system_files')
                    ->where('attachment_type', $type)
                    ->whereIn('attachment_id', $attachmentIds)
                    ->get(['id', 'attachment_id', 'field']);

                foreach ($rows as $row) {
                    if (isset($records[$row->attachment_id][$row->field])) {
                        $ids[] = $row->id;
                    }
                }
            }
        }

        return $ids;
    }

    /**
     * getTranslatableAttachmentRelations returns the translatable attachment relation names keyed by attachment type.
     */
    protected function getTranslatableAttachmentRelations(): array
    {
        $relations = [];

        $types = Db::table('system_files')
            ->whereNotNull('attachment_type')
            ->where('attachment_type', 'not like', '%:%')
            ->distinct()
            ->pluck('attachment_type');

        foreach ($types as $type) {
            if (!$this->matchesModelOption($type)) {
                continue;
            }

            $class = Relation::getMorphedModel($type) ?? $type;
            if (!class_exists($class)) {
                continue;
            }

            $model = new $class;
            if (!method_exists($model, 'methodExists') || !$model->methodExists('getTranslatableAttributes')) {
                continue;
            }

            $names = array_filter($model->getTranslatableAttributes(), function ($name) use ($model) {
                return in_array($model->getRelationType($name), ['attachOne', 'attachMany']);
            });

            if ($names) {
                $relations[$type] = array_values($names);
            }
        }

        return $relations;
    }

    /**
     * applyUpdates writes the new attachment type and field for each file.
     */
    protected function applyUpdates(array $updates)
    {
        foreach ($updates as $id => $values) {
            Db::table('system_files')->where('id', $id)->update($values);
        }
    }

    /**
     * splitLocaleSuffix splits a value at its last colon into the value and locale, or a null locale.
     */
    protected function splitLocaleSuffix(string $value): array
    {
        $position = strrpos($value, ':');
        if ($position === false) {
            return [$value, null];
        }

        return [substr($value, 0, $position), substr($value, $position + 1)];
    }

    /**
     * matchesModelOption returns true when no model filter is given or the type matches it.
     */
    protected function matchesModelOption(string $type): bool
    {
        $model = $this->option('model');

        return !$model || ltrim($model, '\\') === $type;
    }

    /**
     * getOptions
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Skip confirmation prompts'],
            ['rollback', null, InputOption::VALUE_NONE, 'Convert attachments back to the RainLab.Translate format'],
            ['model', null, InputOption::VALUE_REQUIRED, 'Only convert a specific model type'],
            ['default', null, InputOption::VALUE_REQUIRED, 'The default locale, which is stored without a suffix'],
        ];
    }
}
