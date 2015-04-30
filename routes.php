<?php

use RainLab\Translate\Models\Message;
use RainLab\Translate\Classes\Translator;

/*
 * Adds a custom route to check for the locale prefix.
 */
App::before(function($request) {

    $translator = Translator::instance();
    if (!$translator->isConfigured())
        return;

    $locale = Request::segment(1);
    if ($translator->setLocale($locale)) {

        Route::group(['prefix' => $locale], function() use ($locale) {
            Route::any('{slug}', 'Cms\Classes\CmsController@run')->where('slug', '(.*)?');
        });

        Route::any($locale, function() {
            return App::make('Cms\Classes\CmsController')->run('/');
        });
    }

});

/*
 * Save any used messages to the contextual cache.
 */
App::after(function($request) {
    Message::saveToCache();
});
