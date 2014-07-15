<?php namespace RainLab\Translate\Classes;

use App;

/**
 * Translate class
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class Translate
{
    use \October\Rain\Support\Traits\Singleton;

    protected $defaultLocale;

    protected $activeLocale;

    public function init()
    {
        $this->activeLocale = $this->defaultLocale = 'en';
    }

    public function setLocale($locale)
    {
        $this->activeLocale = $locale;
    }

    public function getLocale()
    {
        return $this->activeLocale;
    }

    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

}