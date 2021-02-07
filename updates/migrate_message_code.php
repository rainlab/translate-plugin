<?php namespace RainLab\Translate\Updates;

use Db;
use Str;
use October\Rain\Database\Updates\Migration;
use RainLab\Translate\Models\Message;

class MigrateMessageCode extends Migration
{
    protected $table = 'rainlab_translate_messages';

    public function up()
    {
        foreach (Db::table($this->table)->get() as $message) {
            $default_message = json_decode($message->message_data)->x;
            $code = Message::makeMessageCode($default_message);
            Db::table($this->table)->where('id', $message->id)->update(['code' => $code]);
        }
    }

    public function down()
    {
        foreach (Db::table($this->table)->get() as $message) {
            $default_message = json_decode($message->message_data)->x;
            $code = static::makeLegacyMessageCode($default_message);
            Db::table($this->table)->where('id', $message->id)->update(['code' => $code]);
        }
    }

    public static function makeLegacyMessageCode($messageId)
    {
        $separator = '.';

        // Convert all dashes/underscores into separator
        $messageId = preg_replace('!['.preg_quote('_').'|'.preg_quote('-').']+!u', $separator, $messageId);

        // Remove all characters that are not the separator, letters, numbers, or whitespace.
        $messageId = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', mb_strtolower($messageId));

        // Replace all separator characters and whitespace by a single separator
        $messageId = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $messageId);

        return Str::limit(trim($messageId, $separator), 250);
    }

}
