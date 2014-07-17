<?php namespace RainLab\Translate\Models;

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
     * @var array Object cache of self.
     */
    protected static $cache = [];

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

        if (isset(self::$cache[$code]))
            return self::$cache[$code];

        return self::$cache[$code] = self::whereCode($code)->first();
    }

}