<?php namespace RainLab\Translate\Facades;

use October\Rain\Support\Facade;

class Locale extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor() { return 'locale.class'; }
}
