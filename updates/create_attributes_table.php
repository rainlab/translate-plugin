<?php namespace RainLab\Translate\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateAttributesTable extends Migration
{
    public function up()
    {
        Schema::create('rainlab_translate_attributes', function($table)
        {
            $table->increments('id');
            $table->string('locale', 16)->index();
            $table->integer('model_id')->index()->nullable();
            $table->string('model_type', 512)->index()->nullable();
            $table->mediumText('attribute_data')->nullable();
            $table->index(['model_type', 'model_id', 'locale'], 'translate_attrs_type_id_locale_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rainlab_translate_attributes');
    }
}
