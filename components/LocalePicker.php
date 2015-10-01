<?php namespace RainLab\Translate\Components;

use Redirect;
use RainLab\Translate\Models\Locale as LocaleModel;
use RainLab\Translate\Classes\Translator;
use Cms\Classes\ComponentBase;

class LocalePicker extends ComponentBase
{
    private $translator;

    public $locales;
    public $activeLocale;

    public function componentDetails()
    {
        return [
            'name'        => 'rainlab.translate::lang.locale_picker.component_name',
            'description' => 'rainlab.translate::lang.locale_picker.component_description',
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        $this->translator = Translator::instance();
    }

    public function onRun()
    {
        $this->page['activeLocale'] = $this->activeLocale = $this->translator->getLocale();
        $this->page['locales'] = $this->locales = LocaleModel::listEnabled();
    }

    public function onSwitchLocale()
    {
        if (!$locale = post('locale')) {
            return;
        }

        $this->translator->setLocale($locale);

        return Redirect::to($this->currentPageUrl());
    }
}
