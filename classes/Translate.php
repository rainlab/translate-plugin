<?php namespace RainLab\Translate\Classes;

use App;
use Session;

/**
 * Translate class
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class Translate
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
        $this->defaultLocale = 'en';

        if (Session::has(self::SESSION_LOCALE))
            $this->activeLocale = Session::get(self::SESSION_LOCALE);
        else
            $this->activeLocale = $this->defaultLocale;
    }

    public function setLocale($locale)
    {
        App::setLocale($locale);
        Session::put(self::SESSION_LOCALE, $locale);

        $this->activeLocale = $locale;
    }

    public function getLocale()
    {
        return $this->activeLocale;
    }

    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

}