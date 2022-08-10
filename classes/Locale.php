<?php namespace RainLab\Translate\Classes;

use App;
use Site;
use Model;
use Config;
use October\Rain\Element\ElementBase;
use October\Rain\Database\Collection;

/**
 * Locale definition
 */
class Locale extends ElementBase
{
    /**
     * @var array Object cache of self, by code.
     */
    protected static $cacheByCode = [];

    /**
     * @var array A cache of enabled sites.
     */
    protected static $cacheFromSites;

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
    protected static $defaultLocale;

    /**
     * getDefaultSiteLocale
     */
    public static function getDefaultSiteLocale()
    {
        $site = Site::getPrimarySite();

        if (!$site || !$site->locale) {
            return Config::get('app.locale', 'en');
        }

        return $site->locale;
    }

    /**
     * getSiteLocaleFromContext
     */
    public static function getSiteLocaleFromContext()
    {
        $site = App::runningInBackend()
            ? Site::getEditSite()
            : Site::getActiveSite();

        if (!$site || !$site->locale) {
            return Config::get('app.locale', 'en');
        }

        return $site->locale;
    }

    /**
     * listLocales builds a collection of locales based on site definitions
     */
    public static function listLocales()
    {
        if (self::$cacheFromSites !== null) {
            return self::$cacheFromSites;
        }

        $foundLocales = [];
        $locales = [];
        foreach (Site::listSites() as $site) {
            $localeCode = $site->locale;

            // Prevent duplicates
            if (isset($foundLocales[$localeCode])) {
                continue;
            }

            $locale = new self;
            $locale->is_enabled = $site->is_enabled;
            $locale->name = $site->name;

            if ($site->is_primary) {
                $locale->is_default = true;
                $locale->code = $localeCode ?: Config::get('app.locale', 'en');
            }
            elseif ($localeCode) {
                $locale->is_default = false;
                $locale->code = $localeCode;
            }
            else {
                continue;
            }

            $foundLocales[$localeCode] = true;
            $locales[] = $locale;
        }

        return self::$cacheFromSites = new Collection($locales);
    }

    /**
     * getDefault returns the default locale defined.
     * @return self
     */
    public static function getDefault()
    {
        if (self::$defaultLocale !== null) {
            return self::$defaultLocale;
        }

        return self::$defaultLocale = self::listLocales()->where('is_default', true)->first();
    }

    /**
     * findByCode finds a locale by its code, cached.
     * @param  string $code
     * @return Model
     */
    public static function findByCode($code = null)
    {
        if (!$code) {
            return null;
        }

        if (isset(self::$cacheByCode[$code])) {
            return self::$cacheByCode[$code];
        }

        return self::$cacheByCode[$code] = self::listLocales()->where('code', $code)->first();
    }

    /**
     * isAvailable returns true if there are at least 2 locales available.
     * @return bool
     */
    public static function isAvailable()
    {
        return count(self::listAvailable()) > 1;
    }

    /**
     * listAvailable locales, used on the back-end.
     * @return array
     */
    public static function listAvailable()
    {
        if (self::$cacheListAvailable) {
            return self::$cacheListAvailable;
        }

        return self::$cacheListAvailable = self::listLocales()->pluck('name', 'code')->all();
    }

    /**
     * listEnabled locales, used on the front-end.
     * @return array
     */
    public static function listEnabled()
    {
        if (self::$cacheListEnabled) {
            return self::$cacheListEnabled;
        }

        return self::$cacheListEnabled = self::listLocales()->where('is_enabled', true)->pluck('name', 'code')->all();
    }

    /**
     * isValid returns true if the supplied locale is valid.
     * @return bool
     */
    public static function isValid($locale)
    {
        $languages = array_keys(Locale::listEnabled());

        return in_array($locale, $languages);
    }

    /**
     * clearCache keys used by this model
     */
    public static function clearCache()
    {
        self::$cacheFromSites = null;
        self::$cacheListEnabled = null;
        self::$cacheListAvailable = null;
        self::$cacheByCode = [];
    }
}
