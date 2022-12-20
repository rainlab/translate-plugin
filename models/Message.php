<?php namespace RainLab\Translate\Models;

use App;
use Model;
use Carbon\Carbon;
use RainLab\Translate\Classes\Locale;
use Exception;

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
    protected $jsonable = ['data', 'usage'];

    /**
     * @var array cache for messages
     */
    public static $cache = [];

    /**
     * @var array observeCache for messages
     */
    public static $observeCache = [];

    /**
     * @var int lastFindCount
     */
    protected static $lastFindCount;

    /**
     * trans
     */
    public static function trans($messageId, $params = [], $locale = null)
    {
        return self::translateInternal($messageId, $params, $locale);
    }

    /**
     * transRaw
     */
    public static function transRaw($messageId, $params = [], $locale = null)
    {
        return self::translateInternal($messageId, $params, $locale, true);
    }

    /**
     * translateInternal
     */
    public static function translateInternal($messageId, $params = [], $locale = null, $raw = false)
    {
        if (!$locale) {
            $locale = Locale::getSiteLocaleFromContext();
        }

        if (isset(self::$cache[$locale])) {
            $messages = self::$cache[$locale];
        }
        else {
            $messages = self::$cache[$locale] = (new self)->findMessages($locale);
        }

        $msg = $messages[$messageId] ?? $messageId;

        $params = array_build($params, function($key, $value) use ($raw) {
            return [':'.$key, $raw ? $value : e($value)];
        });

        $msg = strtr($msg, $params);

        self::observeMessage($messageId);

        return $msg;
    }

    /**
     * getMessages
     */
    public static function getMessages($locale = null, $options = [])
    {
        if (!$locale) {
            $locale = App::getLocale();
        }

        return (new self)->findMessages($locale, $options);
    }

    /**
     * @deprecated use importMessageCodes with array_combine or
     * the ThemeScanner class with importMessages method.
     */
    public static function importMessages($messages, $locale = null)
    {
        self::importMessageCodes(array_combine($messages, $messages), $locale);
    }

    /**
     * importMessageCodes
     */
    public static function importMessageCodes($messages, $locale = null)
    {
        if (!$locale) {
            $locale = Locale::getDefaultSiteLocale();
        }

        (new self)->updateObservedMessages($locale, $messages);
    }

    /**
     * saveObserver will save observed messages to the database, this is soft logic
     * since the database table may not exist yet
     */
    public static function saveObserver()
    {
        try {
            $messageKeys = array_keys(self::$observeCache);

            (new self)->updateObservedMessages(
                Locale::getDefaultSiteLocale(),
                array_combine($messageKeys, $messageKeys),
                self::$observeCache
            );
        }
        catch (Exception $ex) {
        }
    }

    /**
     * observeMessage
     */
    public static function observeMessage($messageId)
    {
        self::$observeCache[$messageId] = time();
    }

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
        $this->updateMessagesInternal($messages, ['locale' => $locale]);
    }

    /**
     * updateObservedMessages
     */
    public function updateObservedMessages($locale, $messages, $timestamps = null)
    {
        $this->updateMessagesInternal($messages, [
            'locale' => $locale,
            'timestamps' => $timestamps,
            'overwrite' => false
        ]);
    }

    /**
     * updateMessagesInternal
     */
    protected function updateMessagesInternal($messages, $options = [])
    {
        extract(array_merge([
            'locale' => null,
            'timestamps' => null,
            'overwrite' => true
        ], $options));

        if (!$locale) {
            $locale = Locale::getDefaultSiteLocale();
        }

        $messageData = $messages;

        if ($record = $this->newQuery()->where('locale', $locale)->first()) {
            $data = (array) $record->data;
            $messageData = $overwrite
                ? array_merge($data, (array) $messageData)
                : array_merge((array) $messageData, $data);
        }
        else {
            $record = new self;
            $record->locale = $locale;
        }

        if ($timestamps !== null && is_array($timestamps)) {
            $usage = (array) $record->usage;
            $record->usage = array_merge($usage, $timestamps);
        }

        $record->data = $messageData;
        $record->save();
    }

    /**
     * deleteMessage
     */
    public function deleteMessage($key)
    {
        $this->deleteMessages([$key]);
    }

    /**
     * deleteMessages
     */
    public function deleteMessages(array $key)
    {
        $messages = $this->newQuery()->get();
        foreach ($messages as $record) {
            $data = $record->data;
            foreach ($key as $k) {
                unset($data[$k]);
            }
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
            'withEmpty' => true,
            'withUsage' => false
        ], $options));

        $defaultLocale = Locale::getDefault()->code;

        // Find messages
        $collection = $this->newQuery()->whereIn('locale', $withEmpty
            ? [$locale, $defaultLocale]
            : [$locale]
        )->get();

        $usage = [];
        $messages = [];
        foreach ($collection as $message) {
            $usage[$message->locale] = $message->usage;
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

        // Usage stats
        if ($withUsage) {
            foreach ($result as $key => $message) {
                $time = $usage[$locale][$key] ?? null;
                if ($time) {
                    $result[$key] = (new Carbon($time))->diffForHumans();
                }
                else {
                    $result[$key] = __("Never");
                }
            }
            arsort($result);
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
