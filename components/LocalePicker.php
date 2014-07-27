<?php namespace RainLab\Translate\Components;

use Redirect;
use RainLab\Translate\Models\Locale as LocaleModel;
use RainLab\Translate\Classes\Translate;
use Cms\Classes\ComponentBase;

class LocalePicker extends ComponentBase
{
    private $translator;

    public $locales;
    public $activeLocale;

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

    public function init()
    {
        $this->translator = Translate::instance();
    }

    public function onRun()
    {
        $this->page['activeLocale'] = $this->activeLocale = $this->translator->getLocale();
        $this->page['locales'] = $this->locales = LocaleModel::listEnabled();
    }

    public function onSwitchLocale()
    {
        if (!$locale = post('locale'))
            return;

        $this->translator->setSessionLocale($locale);
        return Redirect::to($this->currentPageUrl());
    }

}