<?php namespace RainLab\Translate\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateMessagesTable extends Migration
{

    public function up()
    {
        Schema::create('rainlab_translate_messages', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('locale')->index();
            $table->string('msg_id')->index()->nullable();
            $table->string('msg_str')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rainlab_translate_messages');
    }

}
