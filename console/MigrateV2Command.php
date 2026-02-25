<?php namespace RainLab\Translate\Console;

use Db;
use Schema;
use Exception;
use Illuminate\Console\Command;

/**
 * MigrateV2Command optimizes translation tables by converting model_id
 * from string to integer and adding composite indexes.
 */
class MigrateV2Command extends Command
{
    /**
     * @var string signature for the console command
     */
    protected $signature = 'translate:migratev2
        {--f|force : Force the operation to run}';

    /**
     * @var string description of the console command
     */
    protected $description = 'Optimizes translation tables for improved query performance';

    /**
     * handle
     */
    public function handle()
    {
        if (
            !$this->option('force') &&
            !$this->confirm('This will optimize the translation database tables. Please make sure you have a backup before proceeding.')
        ) {
            return;
        }

        $this->patchTable(
            'rainlab_translate_attributes',
            'TRANSLATE ATTRIBUTES',
            function($table) {
                $table->string('model_type', 512)->nullable()->change();
                $table->string('locale', 16)->change();
            },
            function($table) {
                $table->index(
                    ['model_type', 'model_id', 'locale'],
                    'translate_attrs_type_id_locale_index'
                );
            }
        );

        $this->patchTable(
            'rainlab_translate_indexes',
            'TRANSLATE INDEXES',
            function($table) {
                $table->string('model_type', 512)->nullable()->change();
                $table->string('locale', 16)->change();
                $table->string('item', 128)->nullable()->change();
            },
            function($table) {
                $table->index(
                    ['model_type', 'model_id', 'locale', 'item'],
                    'translate_idx_type_id_locale_item_index'
                );
            }
        );

        $this->output->success('Translation tables optimized!');
    }

    /**
     * patchTable handles schema and data migration for a single table
     */
    protected function patchTable(string $tableName, string $label, callable $resizeColumns, callable $addIndex)
    {
        $this->line('');
        $this->comment("*** Patching {$label} table");

        if (!Schema::hasTable($tableName)) {
            $this->info("Table [{$tableName}] not found, skipping.");
            return;
        }

        // Schema phase
        if (Schema::hasColumn($tableName, 'str_model_id')) {
            $this->comment('Patch already applied to schema');
        }
        else {
            try {
                $this->comment('Cleaning up indexes');
                Schema::table($tableName, function($table) {
                    $table->dropIndex(['model_id']);
                });
            }
            catch (Exception $ex) {
            }

            try {
                Schema::table($tableName, function($table) {
                    $table->dropIndex(['model_type']);
                });
            }
            catch (Exception $ex) {
            }

            try {
                Schema::table($tableName, function($table) {
                    $table->dropIndex(['locale']);
                });
            }
            catch (Exception $ex) {
            }

            Db::transaction(function() use ($tableName, $resizeColumns, $addIndex) {
                $this->comment('Optimizing columns');

                Schema::table($tableName, function($table) {
                    $table->renameColumn('model_id', 'str_model_id');
                });

                Schema::table($tableName, function($table) {
                    $table->string('str_model_id')->nullable()->change();
                });

                Schema::table($tableName, function($table) {
                    $table->integer('model_id')->nullable()->after('locale');
                });

                Schema::table($tableName, $resizeColumns);

                Schema::table($tableName, $addIndex);
            });
        }

        // Data transfer phase
        $this->line('');
        $this->comment("*** Transferring {$label} data");

        $failedRows = [];
        Db::table($tableName)->whereNull('model_id')->orderBy('id')
            ->chunkById(100, function($rows) use ($tableName, &$failedRows) {
                foreach ($rows as $row) {
                    if (is_null($row->str_model_id)) {
                        // Field is already null
                    }
                    elseif (!is_numeric($row->str_model_id)) {
                        $failedRows[] = $row->id;
                        $this->output->write('!', false);
                    }
                    else {
                        Db::table($tableName)
                            ->where('id', $row->id)
                            ->update(['model_id' => (int) $row->str_model_id]);
                        $this->output->write('.', false);
                    }
                }
            });

        $this->line('');
        $this->comment('Transfer complete');

        if (count($failedRows) > 0) {
            $this->line('');
            $this->warn("Warning! Non-numeric values detected for {$label} rows:");
            $this->warn(sprintf('[%s]', implode(' ', $failedRows)));
            $this->warn('You must address these rows manually, they have not been transferred.');
            $this->line('');
        }
    }
}
