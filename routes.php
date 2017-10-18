<?php

use RainLab\Translate\Models\Message;

/*
 * Adds a custom route to check for the locale prefix.
 */
App::before(function($request) {
    
    Event::listen('cms.beforeRoute', function() {
        
        foreach( \RainLab\Translate\Models\Locale::listAvailable() as $code => $locale ) {   
            Route::middleware('web')->prefix($code)->group( function() {
                Route::any('{slug?}', 'Cms\Classes\CmsController@run')->where( 'slug', '(.*)?' );
            } );
        }
        
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
