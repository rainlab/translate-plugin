<?php

use RainLab\Translate\Models\Locale;

$languages = array_keys(Locale::listFromMetaCache());
$locale = Request::segment(1);

 if (in_array($locale, $languages)) {
    App::setLocale($locale);
    RainLab\Translate\Classes\Translate::instance()->setLocale($locale);

    Route::group(['prefix' => $locale], function() use ($locale) {
        Route::any('{slug}', 'Cms\Classes\Controller@run')->where('slug', '(.*)?');
    });

    Route::any($locale, 'Cms\Classes\Controller@run');
}

App::after(function($request) {
    RainLab\Translate\Models\Message::saveToCache();
});