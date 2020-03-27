<?php namespace RainLab\Translate\Updates;

use October\Rain\Database\Updates\Migration;
use RainLab\Translate\Models\Message;

class MigrateMessageCode extends Migration
{
    public function up()
    {
        foreach (Message::all() as $message) {
            $default_message = $message->message_data['x'];
            $message->code = Message::makeMd5MessageCode($default_message);
            $message->save();
        }
    }

    public function down()
    {
        foreach (Message::all() as $message) {
            $default_message = $message->message_data['x'];
            $message->code = Message::makeMessageCode($default_message);
            $message->save();
        }
    }
}
