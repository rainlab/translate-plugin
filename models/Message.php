<?php namespace RainLab\Translate\Models;

use Model;
use Cache;

/**
 * Message Model
 */
class Message extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'rainlab_translate_messages';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var bool Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    public static $hasNew = false;

    public static $url;

    public static $locale;

    public static $cache = [];

    public static function get($messageId)
    {
        if (!self::$locale)
            return $messageId;

        if (array_key_exists($messageId, self::$cache)) {
            return self::$cache[$messageId];
        }

        $item = static::firstOrCreate([
            'locale' => self::$locale,
            'msg_id' => $messageId
        ]);

        $msg = $item->msg_str ?: $item->msg_id;
        self::$cache[$messageId] = $msg;
        self::$hasNew = true;
        return $msg;
    }

    public static function trans($messageId, $params)
    {
        $msg = static::get($messageId);

        $params = array_build($params, function($key, $value){
            return [':'.$key, $value];
        });

        $msg = strtr($msg, $params);
        return $msg;
    }

    public static function setContext($locale, $url = null)
    {
        if (!strlen($url))
            $url = '/';

        self::$url = $url;
        self::$locale = $locale;

        if ($cached = Cache::get(self::makeCacheKey()))
            self::$cache = (array) $cached;
    }

    public static function saveToCache()
    {
        if (!self::$hasNew || !self::$url || !self::$locale)
            return;

        Cache::put(self::makeCacheKey(), self::$cache, 1440);
    }

    protected static function makeCacheKey()
    {
        return 'translation.'.self::$locale.self::$url;
    }

}