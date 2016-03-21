<?php

use RainLab\Translate\Models\Message;
use RainLab\Translate\Classes\Translator;

/*
 * Adds a custom route to check for the locale prefix.
 */
App::before(function($request) {

    if (App::runningInBackend()) {
        return;
    }

    $translator = Translator::instance();

    if (!$translator->isConfigured()) {
        return;
    }

    if (!$translator->loadLocaleFromRequest()) {
        $translator->loadLocaleFromSession();
        return;
    }

    if (!$locale = $translator->getLocale()) {
        return;
    }

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
    if (class_exists('RainLab\Translate\Models\Message')) {
        Message::saveToCache();
    }
});
