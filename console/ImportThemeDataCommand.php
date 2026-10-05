<?php namespace RainLab\Translate\Console;

use Db;
use Cms\Models\ThemeData;
use Illuminate\Console\Command;
use RainLab\Translate\Models\MLThemeData;
use Symfony\Component\Console\Input\InputOption;

/**
 * ImportThemeDataCommand moves translated theme data and theme files from the plugin theme data model to the core theme data model.
 */
class ImportThemeDataCommand extends Command
{
    /**
     * @var string name
     */
    protected $name = 'translate:import-theme-data';

    /**
     * @var string description
     */
    protected $description = 'Moves translated theme data from RainLab.Translate to the core theme data model';

    /**
     * @var array themeDataModels found by ID, used to decode jsonable values
     */
    protected $themeDataModels = [];

    /**
     * handle
     */
    public function handle()
    {
        if (!method_exists(ThemeData::class, 'getTranslatableAttributes')) {
            $this->error('Translated theme data requires October CMS v4.4 or later.');
            return 1;
        }

        if ($this->option('rollback')) {
            return $this->rollbackThemeData();
        }

        return $this->importThemeData();
    }

    /**
     * importThemeData moves plugin translations to the core table and relabels theme files for the core model.
     */
    protected function importThemeData(): int
    {
        $rows = Db::table('rainlab_translate_attributes')->where('model_type', MLThemeData::class)->get();
        $fileCount = $this->countFiles(MLThemeData::class);

        if (!$rows->count() && !$fileCount) {
            MLThemeData::clearStoredDataCache();
            $this->info('No theme data found to import.');
            return 0;
        }

        $this->info(sprintf('Found %s translation record(s) and %s file(s) to import.', $rows->count(), $fileCount));

        if (!$this->option('force') && !$this->confirm('Proceed with import?')) {
            return 0;
        }

        $values = [];
        foreach ($rows as $row) {
            foreach ((array) json_decode($row->attribute_data, true) as $attribute => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $values[] = [
                    'model_type' => ThemeData::class,
                    'model_id' => (int) $row->model_id,
                    'locale' => $row->locale,
                    'attribute' => $attribute,
                    'value' => is_array($value) ? json_encode($value) : (string) $value
                ];
            }
        }

        Db::transaction(function () use ($values) {
            foreach (array_chunk($values, 500) as $chunk) {
                Db::table('system_translate_attributes')->upsert(
                    $chunk,
                    ['model_type', 'model_id', 'locale', 'attribute'],
                    ['value']
                );
            }

            Db::table('rainlab_translate_attributes')->where('model_type', MLThemeData::class)->delete();

            $this->moveFiles(MLThemeData::class, ThemeData::class);
        });

        MLThemeData::clearStoredDataCache();

        $this->info(sprintf('Imported %s translated value(s) and %s file(s).', count($values), $fileCount));

        return 0;
    }

    /**
     * rollbackThemeData moves core translations back to the plugin table and relabels theme files for the plugin model.
     */
    protected function rollbackThemeData(): int
    {
        $rows = Db::table('system_translate_attributes')->where('model_type', ThemeData::class)->get();
        $fileCount = $this->countFiles(ThemeData::class);

        if (!$rows->count() && !$fileCount) {
            MLThemeData::clearStoredDataCache();
            $this->info('No theme data found to roll back.');
            return 0;
        }

        $this->info(sprintf('Found %s translated value(s) and %s file(s) to roll back.', $rows->count(), $fileCount));

        if (!$this->option('force') && !$this->confirm('Proceed with rollback?')) {
            return 0;
        }

        $records = [];
        foreach ($rows as $row) {
            $records[$row->model_id][$row->locale][$row->attribute] = $this->decodeValue($row->model_id, $row->attribute, $row->value);
        }

        Db::transaction(function () use ($records) {
            foreach ($records as $modelId => $locales) {
                foreach ($locales as $locale => $data) {
                    $this->storePluginTranslations($modelId, $locale, $data);
                }
            }

            Db::table('system_translate_attributes')->where('model_type', ThemeData::class)->delete();

            $this->moveFiles(ThemeData::class, MLThemeData::class);
        });

        MLThemeData::clearStoredDataCache();

        $this->info(sprintf('Rolled back %s translated value(s) and %s file(s).', $rows->count(), $fileCount));

        $localeFileCount = Db::table('system_files')
            ->where('attachment_type', MLThemeData::class)
            ->where('field', 'like', '%:%')
            ->count();

        if ($localeFileCount) {
            $this->warn(sprintf('%s translated file(s) were kept but RainLab.Translate shows the default file for every locale.', $localeFileCount));
        }

        return 0;
    }

    /**
     * storePluginTranslations merges translated values into the plugin record for a locale.
     */
    protected function storePluginTranslations($modelId, string $locale, array $data)
    {
        $query = Db::table('rainlab_translate_attributes')
            ->where('model_type', MLThemeData::class)
            ->where('model_id', $modelId)
            ->where('locale', $locale);

        if ($query->exists()) {
            $existing = (array) json_decode((string) $query->value('attribute_data'), true);
            $query->update(['attribute_data' => json_encode(array_merge($existing, $data))]);
            return;
        }

        Db::table('rainlab_translate_attributes')->insert([
            'model_type' => MLThemeData::class,
            'model_id' => (string) $modelId,
            'locale' => $locale,
            'attribute_data' => json_encode($data)
        ]);
    }

    /**
     * decodeValue returns the stored value as the plugin expects it, decoding jsonable attributes to arrays.
     */
    protected function decodeValue($modelId, string $attribute, $value)
    {
        if (!array_key_exists($modelId, $this->themeDataModels)) {
            $this->themeDataModels[$modelId] = ThemeData::find($modelId);
        }

        $model = $this->themeDataModels[$modelId];

        if ($model && is_string($value) && $model->isJsonable($attribute)) {
            return json_decode($value, true);
        }

        return $value;
    }

    /**
     * moveFiles relabels the theme files attached to one model type for another.
     */
    protected function moveFiles(string $fromType, string $toType)
    {
        Db::table('system_files')->where('attachment_type', $fromType)->update(['attachment_type' => $toType]);
    }

    /**
     * countFiles returns the number of files attached to a model type.
     */
    protected function countFiles(string $type): int
    {
        return Db::table('system_files')->where('attachment_type', $type)->count();
    }

    /**
     * getOptions
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Skip confirmation prompts'],
            ['rollback', null, InputOption::VALUE_NONE, 'Move theme data back to the RainLab.Translate theme data model'],
        ];
    }
}
