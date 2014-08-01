<?php

use RainLab\Translate\Models\Locale;
use RainLab\Translate\Models\Message;
use RainLab\Translate\Classes\Translator;

App::before(function($request) {

    $locale = Request::segment(1);
    $languages = array_keys(Locale::listEnabled());

    if (in_array($locale, $languages)) {
        Translator::instance()->setLocale($locale);

        Route::group(['prefix' => $locale], function() use ($locale) {
            Route::any('{slug}', 'Cms\Classes\Controller@run')->where('slug', '(.*)?');
        });

        Route::any($locale, 'Cms\Classes\Controller@run');
    }

});

App::after(function($request) {
    Message::saveToCache();
});

