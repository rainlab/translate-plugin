<?php namespace RainLab\Translate\Classes;

use App;
use Schema;
use Session;
use DbDongle;
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

    const SESSION_CONFIGURED = 'rainlab.translate.configured';

    /**
     * @var string The locale to use on the front end.
     */
    protected $activeLocale;

    /**
     * @var string The default locale if no active is set.
     */
    protected $defaultLocale;

    /**
     * @var boolean Determine if translate plugin is configured and ready to be used.
     */
    protected $isConfigured;

    /**
     * Initialize the singleton
     * @return void
     */
    public function init()
    {
        $this->defaultLocale = $this->isConfigured() ? array_get(Locale::getDefault(), 'code', 'en') : 'en';
        $this->activeLocale = $this->defaultLocale;
    }

    /**
     * Changes the locale in the application and optionally stores it in the session.
     * @param   string  $locale   Locale to use
     * @param   boolean $remember Set to false to not store in the session.
     * @return  boolean Returns true if the locale exists and is set.
     */
    public function setLocale($locale, $remember = true)
    {
        if (!Locale::isValid($locale)) {
            return false;
        }

        App::setLocale($locale);
        $this->activeLocale = $locale;

        if ($remember) {
            $this->setSessionLocale($locale);
        }

        return true;
    }

    /**
     * Returns the active locale set by this instance.
     * @param  boolean $fromSession Look in the session.
     * @return string
     */
    public function getLocale($fromSession = false)
    {
        if ($fromSession && ($locale = $this->getSessionLocale())) {
            return $locale;
        }

        return $this->activeLocale;
    }

    /**
     * Returns the default locale as set by the application.
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Check if this plugin is installed and the database is available, 
     * stores the result in the session for efficiency.
     * @return boolean
     */
    public function isConfigured()
    {
        if ($this->isConfigured !== null) {
            return $this->isConfigured;
        }

        if (Session::has(self::SESSION_CONFIGURED)) {
            $result = true;
        }
        elseif (DbDongle::hasDatabase() && Schema::hasTable('rainlab_translate_locales')) {
            Session::put(self::SESSION_CONFIGURED, true);
            $result = true;
        }
        else {
            $result = false;
        }

        return $this->isConfigured = $result;
    }

    //
    // Session handling
    //

    public function loadLocaleFromSession()
    {
        if ($sessionLocale = $this->getSessionLocale()) {
            $this->setLocale($sessionLocale);
        }
    }

    protected function getSessionLocale()
    {
        if (!Session::has(self::SESSION_LOCALE)) {
            return null;
        }

        return Session::get(self::SESSION_LOCALE);
    }

    protected function setSessionLocale($locale)
    {
        Session::put(self::SESSION_LOCALE, $locale);
    }
}
