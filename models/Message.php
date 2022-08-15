<?php namespace RainLab\Translate\Models;

use Model;
use RainLab\Translate\Classes\Locale;

/**
 * Message Model
 */
class Message extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'rainlab_translate_message_data';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array List of attribute names which are json encoded and decoded from the database.
     */
    protected $jsonable = ['data'];

    /**
     * @var int lastFindCount
     */
    protected static $lastFindCount;

    /**
     * updateMessage
     */
    public function updateMessage($locale, $key, $message)
    {
        $this->updateMessages($locale, [$key => $message]);
    }

    /**
     * updateMessage
     */
    public function updateMessages($locale, $messages)
    {
        $messageData = $messages;

        if ($record = $this->newQuery()->where('locale', $locale)->first()) {
            $data = (array) $record->data;
            $messageData = array_merge($data, $messageData);
        }
        else {
            $record = new self;
            $record->locale = $locale;
        }

        $record->data = $messageData;
        $record->save();
    }

    /**
     * deleteMessage
     */
    public function deleteMessage($key)
    {
        $messages = $this->newQuery()->get();
        foreach ($messages as $record) {
            $data = $record->data;
            unset($data[$key]);
            $record->data = $data;
            $record->save();
        }
    }

    /**
     * findMessages
     */
    public function findMessages($locale, $options = [])
    {
        extract(array_merge([
            'search' => null,
            'offset' => null,
            'count' => null,
            'withEmpty' => false
        ], $options));

        $defaultLocale = Locale::getDefault()->code;

        // Find messages
        $collection = $this->newQuery()->whereIn('locale', $withEmpty
            ? [$locale, $defaultLocale]
            : [$locale]
        )->get();

        $messages = [];
        foreach ($collection as $message) {
            $messages[$message->locale] = $message->data;
        }

        // Process
        if ($withEmpty) {
            $result = [];
            $emptyMessages = $messages[$defaultLocale] ?? [];
            $sourceMessages = $messages[$locale] ?? [];
            foreach ($emptyMessages as $key => $message) {
                $result[$key] = $sourceMessages[$key] ?? null;
            }
        }
        else {
            $result = $messages[$locale] ?? [];
        }

        // Search
        if ($search) {
            $result = $this->applySearchToResult($result, $search);
        }

        // Remember count
        self::$lastFindCount = count($result);

        // Count
        if ($count) {
            $result = $this->applyCountToResult($result, $count, $offset);
        }

        return $result;
    }

    /**
     * getLastCount
     */
    public static function getLastCount(): int
    {
        return self::$lastFindCount;
    }

    /**
     * applyCountToResult
     */
    public function applyCountToResult($result, $count, $offset)
    {
        return array_slice($result, $offset ?: 0, $count);
    }

    /**
     * applySearchToResult
     */
    public function applySearchToResult($result, $search)
    {
        foreach ($result as $key => $message) {
            if (
                stripos($message, $search) === false &&
                stripos($key, $search) === false
            ) {
                unset($result[$key]);
            }
        }

        return $result;
    }
}
