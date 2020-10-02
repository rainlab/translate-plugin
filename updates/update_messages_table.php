<?php namespace RainLab\Translate\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UpdateMessagesTable extends Migration
{
    const TABLE_NAME = 'rainlab_translate_messages';

    public function up()
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        if (!Schema::hasColumn(self::TABLE_NAME, 'found')) {
            Schema::table(self::TABLE_NAME, function($table)
            {
                $table->boolean('found')->default(1);
            });
        }
    }

    public function down()
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        if (Schema::hasColumn(self::TABLE_NAME, 'found')) {
            Schema::table(self::TABLE_NAME, function($table)
            {
                $table->dropColumn(['found']);
            });
        }
    }
}
