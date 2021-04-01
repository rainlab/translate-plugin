<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Force the Default Locale
    |--------------------------------------------------------------------------
    |
    | Always use the defined locale code as the default.
    | Related to https://github.com/rainlab/translate-plugin/issues/231
    |
    */
    'forceDefaultLocale' => env('TRANSLATE_FORCE_LOCALE', null),

    /*
    |--------------------------------------------------------------------------
    | Prefix the Default Locale
    |--------------------------------------------------------------------------
    |
    | Specifies if the default locale be prefixed by the plugin.
    |
    */
    'prefixDefaultLocale' => env('TRANSLATE_PREFIX_LOCALE', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Timeout in Minutes
    |--------------------------------------------------------------------------
    |
    | By default all translations are cached for 24 hours (1440 min).
    | This setting allows to change that period with given amount of minutes.
    |
    | For example, 43200 for 30 days or 525600 for one year.
    |
    */
    'cacheTimeout' => env('TRANSLATE_CACHE_TIMEOUT', 1440),

    /*
    |--------------------------------------------------------------------------
    | Disable Locale Prefix Routes
    |--------------------------------------------------------------------------
    |
    | Disables the automatically generated locale prefixed routes
    | (i.e. /en/original-route) when enabled.
    |
    */
    'disableLocalePrefixRoutes' => env('TRANSLATE_DISABLE_PREFIX_ROUTES', false),

];
