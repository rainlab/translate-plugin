<?php namespace RainLab\Translate\FormWidgets;

use RainLab\Translate\Models\Locale;
use Backend\Classes\FormWidgetBase;

/**
 * ML Text
 * Renders a multi-lingual text field.
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class MLText extends FormWidgetBase
{
    /**
     * {@inheritDoc}
     */
    public $defaultAlias = 'mltext';

    /**
     * @var string Form field column name.
     */
    public $columnName;

    /**
     * @var boolean Determines whether translation services are available
     */
    public $isAvailable;

    public function init()
    {
        $this->columnName  = $this->formField->columnName;
        $this->defaultLocale  = Locale::getDefault();
    }

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $this->isAvailable = Locale::isAvailable();

        $this->prepareVars();
        return $this->makePartial($this->isAvailable ? 'mltext' : 'text');
    }

    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        $this->vars['defaultLocale'] = $this->defaultLocale;
        $this->vars['locales'] = Locale::listAvailable();
        $this->vars['field'] = $this->makeRenderFormField();
    }

    public function getLocaleValue($locale)
    {
        if ($this->model->methodExists('getTranslateAttribute'))
            return $this->model->getTranslateAttribute($this->columnName, $locale);
        else
            return $this->formField->value;
    }

    /**
     * If translation is unavailable, render the original field type (text).
     */
    protected function makeRenderFormField()
    {
        if ($this->isAvailable)
            return $this->formField;

        $field = clone $this->formField;
        $field->type = 'text';
        return $field;
    }

    /**
     * {@inheritDoc}
     */
    public function loadAssets()
    {
        $this->addJs('/plugins/rainlab/translate/assets/js/multilingual.js', 'RainLab.Translate');
        $this->addCss('/plugins/rainlab/translate/assets/css/multilingual.css', 'RainLab.Translate');
    }

    /**
     * {@inheritDoc}
     */
    public function getSaveData($value)
    {
        $localeData = $this->getLocaleSaveData();

        /*
         * Set the translated values to the model
         */
        if ($this->model->methodExists('setTranslateAttribute')) {
            foreach ($localeData as $locale => $value) {
                $this->model->setTranslateAttribute($this->columnName, $value, $locale);
            }
        }

        return array_get($localeData, $this->defaultLocale->code, $value);
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

}