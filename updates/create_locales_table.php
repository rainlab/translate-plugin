<?php namespace RainLab\Translate\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateLocalesTable extends Migration
{

    public function up()
    {
        Schema::create('rainlab_translate_locales', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('code')->index();
            $table->string('name')->index()->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rainlab_translate_locales');
    }

}
