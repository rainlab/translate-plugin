<?php namespace RainLab\Translate\Classes\EventRegistry;

use App;
use Event;
use System\Classes\MailManager;

/**
 * MailHandler for mail template localization events
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class MailHandler
{
    /**
     * register events
     */
    public function register()
    {
        $this->extendSystemMailerContent();
    }

    /**
     * boot events
     */
    public function boot()
    {
    }

    /**
     * extendSystemMailerContent loads localized version of mail templates
     */
    protected function extendSystemMailerContent()
    {
        Event::listen('mailer.beforeAddContent', function ($mailer, $message, $view, $data, $raw, $plain) {
            // Raw content cannot be localized at this level
            if (!empty($raw)) {
                return;
            }

            // Closures cannot be localized as file-based views
            if ((!empty($view) && !is_string($view)) || (!empty($plain) && !is_string($plain))) {
                return;
            }

            // Get the locale to use for this template
            $locale = !empty($data['_current_locale']) ? $data['_current_locale'] : App::getLocale();

            $factory = $mailer->getViewFactory();

            if (!empty($view)) {
                $view = $this->getLocalizedView($factory, $view, $locale);
            }

            if (!empty($plain)) {
                $plain = $this->getLocalizedView($factory, $plain, $locale);
            }

            $code = $view ?: $plain;
            if (empty($code)) {
                return null;
            }

            $plainOnly = empty($view);

            // Caller firing the event is expecting a FALSE response to halt the event
            if (MailManager::instance()->addContentToMailer($message, $code, $data, $plainOnly)) {
                return false;
            }
        }, 1);
    }

    /**
     * getLocalizedView searches mail view files based on locale
     * @param  \Illuminate\View\Factory $factory
     * @param  string $code
     * @param  string $locale
     * @return string|null
     */
    public function getLocalizedView($factory, $code, $locale)
    {
        $locale = strtolower($locale);
        $searchPaths[] = $locale;

        if (str_contains($locale, '-')) {
            list($lang) = explode('-', $locale);
            $searchPaths[] = $lang;
        }

        foreach ($searchPaths as $path) {
            $localizedView = sprintf('%s-%s', $code, $path);

            if ($factory->exists($localizedView)) {
                return $localizedView;
            }
        }

        return null;
    }
}
