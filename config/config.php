<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Skip Session
    |--------------------------------------------------------------------------
    |
    | When using the rainlab.Translate plugin it sometimes is necessary to
    | skip te retrieval of the locale from the session in order to let
    | the main-root of the domain stay in the default language. (instead of
    | showing the last visited locale = locale stored in the session)
    | default = false
    */
    'skipSession' => false
];
