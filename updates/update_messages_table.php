<?php namespace RainLab\Translate\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class UpdateMessagesTable extends Migration
{
    public function up()
    {
        Schema::table('rainlab_translate_messages', function($table)
        {
            $table->boolean('found')->default(1);
        });
    }

    public function down()
    {
        Schema::table('rainlab_translate_messages', function($table)
        {
            $table->dropColumn('found');
        });
    }
}