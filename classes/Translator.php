<?php namespace RainLab\Translate\Classes;

use App;
use Session;
use RainLab\Translate\Models\Locale;

/**
 * Translate class
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class Translator
{

    use \October\Rain\Support\Traits\Singleton;

    const SESSION_LOCALE = 'rainlab.translate.locale';

    /**
     * @var string The locale to use on the front end.
     */
    protected $activeLocale;

    /**
     * @var string The default locale if no active is set.
     */
    protected $defaultLocale;

    public function init()
    {
        $this->activeLocale = $this->defaultLocale = array_get(Locale::getDefault(), 'code', 'en');
    }

    public function setLocale($locale)
    {
        App::setLocale($locale);
        $this->activeLocale = $locale;
    }

    public function getLocale($fromSession = false)
    {
        return $this->activeLocale;
    }

    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    //
    // Session handling
    //

    public function getSessionLocale()
    {
        if (!Session::has(self::SESSION_LOCALE))
            return null;

        return Session::get(self::SESSION_LOCALE);
    }

    public function setSessionLocale($locale)
    {
        Session::put(self::SESSION_LOCALE, $locale);
    }

    public function loadLocaleFromSession()
    {
        if ($sessionLocale = $this->getSessionLocale())
            $this->setLocale($sessionLocale);
    }

}