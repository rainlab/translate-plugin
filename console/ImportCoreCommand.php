<?php namespace RainLab\Translate\Console;

use Db;
use Schema;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * ImportCoreCommand migrates translation data from RainLab.Translate tables
 * to the core system_translate_attributes table.
 *
 * Converts JSON blobs in rainlab_translate_attributes into per-row records,
 * then imports indexed values from rainlab_translate_indexes.
 */
class ImportCoreCommand extends Command
{
    /**
     * @var string name
     */
    protected $name = 'translate:import-attributes';

    /**
     * @var string description
     */
    protected $description = 'Migrates translation data from RainLab.Translate to the core Translatable trait';

    /**
     * @var int migratedCount
     */
    protected $migratedCount = 0;

    /**
     * @var int skippedCount
     */
    protected $skippedCount = 0;

    /**
     * @var int failedCount
     */
    protected $failedCount = 0;

    /**
     * handle
     */
    public function handle()
    {
        // Validate tables exist
        if (!Schema::hasTable('rainlab_translate_attributes')) {
            $this->error('Source table [rainlab_translate_attributes] not found.');
            return 1;
        }

        if (!Schema::hasTable('system_translate_attributes')) {
            $this->error('Target table [system_translate_attributes] not found. Run php artisan october:migrate first.');
            return 1;
        }

        // Count source records
        $attrQuery = Db::table('rainlab_translate_attributes');
        $indexQuery = Db::table('rainlab_translate_indexes');

        if ($modelType = $this->option('model')) {
            $attrQuery->where('model_type', $modelType);
            $indexQuery->where('model_type', $modelType);
        }

        $attrCount = $attrQuery->count();
        $indexCount = Schema::hasTable('rainlab_translate_indexes') ? $indexQuery->count() : 0;

        if ($attrCount === 0 && $indexCount === 0) {
            $this->info('No records found to migrate.');
            return 0;
        }

        $this->info("Found {$attrCount} attribute record(s) and {$indexCount} index record(s) to migrate.");

        if (!$this->option('force') && !$this->confirm('Proceed with migration?')) {
            return 0;
        }

        // Phase 1: Import attribute data (JSON blobs)
        $this->info('Importing attribute data...');
        $this->importAttributes();

        // Phase 2: Import index data
        if ($indexCount > 0) {
            $this->info('');
            $this->info('Importing index data...');
            $this->importIndexes();
        }

        // Report
        $this->newLine();
        $this->info("Migration complete: {$this->migratedCount} migrated, {$this->skippedCount} skipped, {$this->failedCount} failed.");

        if ($this->failedCount > 0) {
            $this->warn('Some records failed to migrate. Check the output above for details.');
        }

        // Cleanup
        if ($this->option('cleanup')) {
            if (!$this->option('force') && !$this->confirm('Truncate source tables? This cannot be undone.')) {
                return 0;
            }

            Db::table('rainlab_translate_attributes')->truncate();
            $this->info('Truncated rainlab_translate_attributes.');

            if (Schema::hasTable('rainlab_translate_indexes')) {
                Db::table('rainlab_translate_indexes')->truncate();
                $this->info('Truncated rainlab_translate_indexes.');
            }
        }

        return 0;
    }

    /**
     * importAttributes converts JSON blobs from rainlab_translate_attributes
     * into per-row records in system_translate_attributes
     */
    protected function importAttributes()
    {
        $query = Db::table('rainlab_translate_attributes');

        if ($modelType = $this->option('model')) {
            $query->where('model_type', $modelType);
        }

        $query->orderBy('id')->chunk(100, function ($rows) {
            $upsertRows = [];

            foreach ($rows as $row) {
                if (empty($row->model_id) || !is_numeric($row->model_id)) {
                    $this->skippedCount++;
                    continue;
                }

                $data = json_decode($row->attribute_data, true);
                if (!is_array($data) || empty($data)) {
                    $this->skippedCount++;
                    continue;
                }

                foreach ($data as $attribute => $value) {
                    if ($value === null || $value === '') {
                        continue;
                    }

                    $storeValue = is_array($value) ? json_encode($value) : (string) $value;

                    $upsertRows[] = [
                        'model_type' => $row->model_type,
                        'model_id' => (int) $row->model_id,
                        'locale' => $row->locale,
                        'attribute' => $attribute,
                        'value' => $storeValue,
                    ];
                }
            }

            if (!empty($upsertRows)) {
                try {
                    Db::table('system_translate_attributes')->upsert(
                        $upsertRows,
                        ['model_type', 'model_id', 'locale', 'attribute'],
                        ['value']
                    );
                    $this->migratedCount += count($upsertRows);
                    $this->output->write('.');
                }
                catch (\Exception $e) {
                    $this->failedCount += count($upsertRows);
                    $this->output->write('!');
                }
            }
        });
    }

    /**
     * importIndexes imports indexed values from rainlab_translate_indexes that
     * may not already exist in the attributes table
     */
    protected function importIndexes()
    {
        if (!Schema::hasTable('rainlab_translate_indexes')) {
            return;
        }

        $query = Db::table('rainlab_translate_indexes');

        if ($modelType = $this->option('model')) {
            $query->where('model_type', $modelType);
        }

        $query->orderBy('id')->chunk(100, function ($rows) {
            $upsertRows = [];

            foreach ($rows as $row) {
                if (empty($row->model_id) || !is_numeric($row->model_id)) {
                    $this->skippedCount++;
                    continue;
                }

                if (empty($row->item) || $row->value === null) {
                    $this->skippedCount++;
                    continue;
                }

                $upsertRows[] = [
                    'model_type' => $row->model_type,
                    'model_id' => (int) $row->model_id,
                    'locale' => $row->locale,
                    'attribute' => $row->item,
                    'value' => (string) $row->value,
                ];
            }

            if (!empty($upsertRows)) {
                try {
                    Db::table('system_translate_attributes')->upsert(
                        $upsertRows,
                        ['model_type', 'model_id', 'locale', 'attribute'],
                        ['value']
                    );
                    $this->migratedCount += count($upsertRows);
                    $this->output->write('.');
                }
                catch (\Exception $e) {
                    $this->failedCount += count($upsertRows);
                    $this->output->write('!');
                }
            }
        });
    }

    /**
     * getOptions
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Skip confirmation prompts'],
            ['cleanup', null, InputOption::VALUE_NONE, 'Truncate source tables after migration'],
            ['model', null, InputOption::VALUE_REQUIRED, 'Only migrate a specific model type'],
        ];
    }
}
