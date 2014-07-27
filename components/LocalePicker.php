<?php namespace RainLab\Translate\Components;

use Cms\Classes\ComponentBase;

class LocalePicker extends ComponentBase
{

    public function componentDetails()
    {
        return [
            'name'        => 'Locale Picker',
            'description' => 'Shows a dropdown to select a front-end language.'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

}