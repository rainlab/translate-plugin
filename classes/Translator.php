<?php namespace RainLab\Translate\Classes;

use App;
use Cms;
use Site;
use Event;
use Schema;
use Session;
use Request;
use Cms\Classes\Page as CmsPage;
use RainLab\Translate\Classes\Locale;

/**
 * Translator class
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
        $this->defaultLocale = Locale::getDefaultSiteLocale();
        $this->activeLocale = Locale::getSiteLocaleFromContext();

        // Reset locale when active and edit sites change
        Event::listen('system.site.setEditSite', function() {
            $this->activeLocale = Locale::getSiteLocaleFromContext();
        });

        Event::listen('system.site.setActiveSite', function() {
            $this->activeLocale = Locale::getSiteLocaleFromContext();
        });
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
        elseif (App::hasDatabase() && Schema::hasTable('rainlab_translate_locales')) {
            Session::put(self::SESSION_CONFIGURED, true);
            $result = true;
        }
        else {
            $result = false;
        }

        return $this->isConfigured = $result;
    }

    //
    // Request handling
    //

    /**
     * getCurrentPathInLocale returns the current path prefixed with language code.
     *
     * @param string $locale optional language code, default to the system default language
     * @return string
     */
    public function getCurrentPathInLocale($locale = null)
    {
        return $this->getPathInLocale(Request::path(), $locale);
    }

    /**
     * getPathInLocale returns the path prefixed with language code. The path to rewrite,
     * can be already translated, with or without locale prefixed.
     *
     * @param string $path
     * @param string $locale
     * @return string|null
     */
    public function getPathInLocale($path, $locale = null)
    {
        if (!$locale || !Locale::isValid($locale)) {
            $locale = $this->defaultLocale;
        }

        $site = Site::getSiteForLocale($locale);
        if (!$site) {
            return $path;
        }

        $newPath = $site->removeRoutePrefix($path);
        $newPath = $site->attachRoutePrefix($newPath);

        return $newPath;
    }

    /**
     * getPageInLocale returns a page URL for a given locale
     *
     * @param string $path
     * @param string $locale
     * @param array $params
     * @return string|null
     */
    public function getPageInLocale($name, $locale = null, $params = [])
    {
        $page = CmsPage::find($name);
        if (!$name || !$page) {
            return null;
        }

        if (!$locale || !Locale::isValid($locale)) {
            $locale = $this->defaultLocale;
        }

        $site = Site::getSiteForLocale($locale);
        if (!$site) {
            return null;
        }

        $router = new \October\Rain\Router\Router;
        $urlPattern = array_get($page->attributes, 'viewBag.localeUrl.'.$locale, $page->url);

        $newPath = trim($router->urlFromPattern($urlPattern, $params), '/');
        $newPath = $site->attachRoutePrefix($newPath);

        return Cms::url($newPath);
    }

    //
    // Session handling
    //

    /**
     * Looks at the session storage to find a locale.
     * @return bool
     */
    public function loadLocaleFromSession()
    {
        $locale = $this->getSessionLocale();

        if (!$locale) {
            return false;
        }

        $this->setLocale($locale);
        return true;
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
