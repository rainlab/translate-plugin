<?php namespace RainLab\Translate\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateMessageDataTable extends Migration
{
    public function up()
    {
        Schema::create('rainlab_translate_message_data', function($table)
        {
            $table->increments('id');
            $table->string('locale')->index()->nullable();
            $table->longText('data')->nullable();
            $table->longText('usage')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rainlab_translate_message_data');
    }
}
