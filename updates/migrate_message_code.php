<?php namespace RainLab\Translate\Updates;

use Schema;
use Str;
use October\Rain\Database\Updates\Migration;
use RainLab\Translate\Models\Message;

class MigrateMessageCode extends Migration
{
    const TABLE_NAME = 'rainlab_translate_messages';

    public function up()
    {
        foreach (Message::all() as $message) {
            $default_message = $message->message_data['x'];
            $message->code = Message::makeMessageCode($default_message);
            $message->save();
        }
    }

    public function down()
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        foreach (Message::all() as $message) {
            $default_message = $message->message_data['x'];
            $message->code = static::makeLegacyMessageCode($default_message);
            $message->save();
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
