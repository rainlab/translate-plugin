<?php

$languages = ['en', 'ru'];
$locale = Request::segment(1);

if (in_array($locale, $languages)) {
    App::setLocale($locale);

    Route::group(['prefix' => $locale], function() use ($locale) {
        Route::any('{slug}', 'Cms\Classes\Controller@run')->where('slug', '(.*)?');
    });

    Route::any($locale, 'Cms\Classes\Controller@run');
}

App::after(function($request) {
    RainLab\Translate\Models\Message::saveToCache();
});