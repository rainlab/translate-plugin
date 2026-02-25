<?php namespace RainLab\Translate\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateIndexesTable extends Migration
{
    public function up()
    {
        Schema::create('rainlab_translate_indexes', function($table)
        {
            $table->increments('id');
            $table->string('locale', 16)->index();
            $table->integer('model_id')->index()->nullable();
            $table->string('model_type', 512)->index()->nullable();
            $table->string('item', 128)->nullable()->index();
            $table->mediumText('value')->nullable();
            $table->index(['model_type', 'model_id', 'locale', 'item'], 'translate_idx_type_id_locale_item_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rainlab_translate_indexes');
    }
}
