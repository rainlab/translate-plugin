<?php

use RainLab\Translate\Models\Locale;
use RainLab\Translate\Models\Message;
use RainLab\Translate\Models\Preferences;
use RainLab\Translate\Classes\Translator;

/*
 * Adds a custom route to check for the locale prefix.
 */
App::before(function($request) {

    $translator = Translator::instance();
    if (!$translator->isConfigured())
        return;

    $locale = Request::segment(1);

    // Redirect CMS routes without language code prefix if the
    // `always_prefix_language_code` preference is turned on.
    if (!Locale::isValid($locale)) {

        // do not redirect if the setting isn't turned on,
        // or if the requested path being a backend route.
        if (!Preferences::get('always_prefix_language_code') ||
            ltrim(Config::get('cms.backendUri'), '/') == Request::segment(1)) {
            return;
        }

        try {
            // determine if the requested path matches a route defined in other plugins.
            Route::getRoutes()->match($request);

            return;
        }
        catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $exception) {
            return Redirect::to($translator->getCurrentPathInLocale($translator->getLocale(true)));
        }

    }

    $translator->setLocale($locale);

    /*
     * Register routes
     */
    Route::group(['prefix' => $locale], function() {
        Route::any('{slug}', 'Cms\Classes\CmsController@run')->where('slug', '(.*)?');
    });

    Route::any($locale, 'Cms\Classes\CmsController@run');

    /*
     * Ensure Url::action() retains the localized URL
     * by re-registering the route after the CMS.
     */
    Event::listen('cms.route', function() use ($locale) {
        Route::group(['prefix' => $locale], function() {
            Route::any('{slug}', 'Cms\Classes\CmsController@run')->where('slug', '(.*)?');
        });
    });

});

/*
 * Save any used messages to the contextual cache.
 */
App::after(function($request) {
    Message::saveToCache();
});
