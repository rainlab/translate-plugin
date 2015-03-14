<?php namespace RainLab\Translate\Traits;

use RainLab\Translate\Models\Locale;
use Backend\Classes\FormWidgetBase;
use Session;

/**
 * Generic ML Control
 * Renders a multi-lingual control.
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
trait MLControl
{

    /**
     * @var string Form field column name.
     */
    public $columnName;

    /**
     * @var boolean Determines whether translation services are available
     */
    public $isAvailable;

    /**
     * @var string Specifies a path to the views directory.
     */
    protected $parentViewPath;

    /**
     * Initialize control
     * @return void
     */
    public function initLocale()
    {
        $this->columnName  = $this->formField->fieldName;
        $this->defaultLocale  = Locale::getDefault();
        $this->parentViewPath = $this->guessViewPathFrom(__TRAIT__, '/partials');
        $this->isAvailable = Locale::isAvailable();
    }

    /**
     * {@inheritDoc}
     */
    public function renderFallbackField()
    {
        return $this->makeParentPartial('fallback_field');
    }

    /**
     * Used by child classes to render in context of this view path.
     * @param string $partial The view to load.
     * @param array $params Parameter variables to pass to the view.
     * @return string The view contents.
     */
    public function makeParentPartial($partial, $params = [])
    {
        $oldViewPath = $this->viewPath;
        $this->viewPath = $this->parentViewPath;
        $result = $this->makePartial($partial, $params);
        $this->viewPath = $oldViewPath;
        return $result;
    }

    /**
     * Prepares the list data
     */
    public function prepareLocaleVars()
    {
        $this->vars['defaultLocale'] = $this->defaultLocale;
        $this->vars['locales'] = Locale::listAvailable();
        $this->vars['field'] = $this->makeRenderFormField();
    }

    /**
     * Loads assets specific to ML Controls
     */
    public function loadLocaleAssets()
    {
        $this->addJs('/plugins/rainlab/translate/assets/js/multilingual.js', 'RainLab.Translate');
        $this->addCss('/plugins/rainlab/translate/assets/css/multilingual.css', 'RainLab.Translate');
    }

    /**
     * Returns a translated value for a given locale.
     * @param  string $locale
     * @return string
     */
    public function getLocaleValue($locale)
    {
        if ($this->model->methodExists('getTranslateAttribute')) {
            return $this->model->noFallbackLocale()->getTranslateAttribute($this->columnName, $locale);
        }
        else {
            return $this->formField->value;
        }
    }

    /**
     * If translation is unavailable, render the original field type (text).
     */
    protected function makeRenderFormField()
    {
        if ($this->isAvailable)
            return $this->formField;

        $field = clone $this->formField;
        $field->type = $this->getFallbackType();
        return $field;
    }

    /**
     * {@inheritDoc}
     */
    public function getSaveValue($value)
    {
        $localeData = $this->getLocaleSaveData();

        foreach ($localeData as $locale => $value) {
            $this->setTranslateAttribute($this->columnName, $value, $locale);
        }

        return array_get($localeData, $this->defaultLocale->code, $value);
    }

    /*
     * Set the translated values to the model
     */
    public function setTranslateAttribute($key, $value, $locale)
    {
        $data = [['key' => $key, 'value' => $value, 'locale' => $locale]];
        $attributes = Session::get('RLTranslate.localeAttributes', []);
        $attributes = array_merge($attributes, $data);
        Session::put('RLTranslate.localeAttributes', $attributes);
    }

    /**
     * Returns an array of translated values for this field
     * @return array
     */
    public function getLocaleSaveData()
    {
        $data = post('RLTranslate');
        if (!is_array($data))
            return [];

        $values = [];
        foreach ($data as $locale => $_data) {
            $values[$locale] = array_get($_data, $this->columnName);
        }

        return $values;
    }

    /**
     * Returns the fallback field type.
     * @return string
     */
    public function getFallbackType()
    {
        return defined('static::FALLBACK_TYPE') ? static::FALLBACK_TYPE : 'text';
    }

}
