<?php namespace RainLab\Translate\Models;

use Cache;
use Model;
use October\Rain\Support\ValidationException;

/**
 * Locale Model
 */
class Locale extends Model
{

    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'rainlab_translate_locales';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'code' => 'required',
        'name' => 'required',
    ];

    public $timestamps = false;

    /**
     * @var array Object cache of self, by code.
     */
    protected static $cacheByCode = [];

    /**
     * @var array A cache of enabled locales.
     */
    protected static $cacheListEnabled;

    /**
     * @var array A cache of available locales.
     */
    protected static $cacheListAvailable;

    /**
     * @var self Default locale cache.
     */
    private static $defaultLocale;

    public function afterCreate()
    {
        if ($this->is_default)
            $this->makeDefault();
    }

    public function beforeUpdate()
    {
        if ($this->isDirty('is_default')) {
            $this->makeDefault();

            if (!$this->is_default)
                throw new ValidationException(['is_default' => sprintf('"%s" is already default and cannot be unset as default.', $this->name)]);
        }
    }

    public function makeDefault()
    {
        if (!$this->is_enabled)
            throw new ValidationException(['is_enabled' => sprintf('"%s" is disabled and cannot be set as default.', $this->name)]);

        $this->newQuery()->where('id', $this->id)->update(['is_default' => true]);
        $this->newQuery()->where('id', '<>', $this->id)->update(['is_default' => false]);
    }

    public static function getDefault()
    {
        if (self::$defaultLocale !== null)
            return self::$defaultLocale;

        return self::$defaultLocale = self::where('is_default', true)->first();
    }

    /**
     * Locate a locale table by its code, cached.
     * @param  string $code
     * @return Model
     */
    public static function findByCode($code = null)
    {
        if (!$code)
            return null;

        if (isset(self::$cacheByCode[$code]))
            return self::$cacheByCode[$code];

        return self::$cacheByCode[$code] = self::whereCode($code)->first();
    }

    public function scopeIsEnabled($query)
    {
        return $query
            ->whereNotNull('is_enabled')
            ->where('is_enabled', true)
        ;
    }

    /**
     * Returns true if there are at least 2 locales available.
     * @return boolean
     */
    public static function isAvailable()
    {
        return count(self::listAvailable()) > 1;
    }

    /**
     * Lists available locales, used on the back-end.
     * @return array
     */
    public static function listAvailable()
    {
        if (self::$cacheListAvailable)
            return self::$cacheListAvailable;

        return self::$cacheListAvailable = self::lists('name', 'code');
    }

    /**
     * Lists the enabled locales, used on the front-end.
     * @return array
     */
    public static function listEnabled()
    {
        $cacheKey = 'translate.locales';

        if (self::$cacheListEnabled)
            return self::$cacheListEnabled;

        if ($cached = Cache::get($cacheKey))
            return self::$cacheListEnabled = (array) $cached;

        $enabled = self::isEnabled()->lists('name', 'code');
        Cache::put($cacheKey, $enabled, 1440);
        return self::$cacheListEnabled = $enabled;
    }

}