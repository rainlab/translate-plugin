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
     * @var boolean Determines whether translation services are available
     */
    public $isAvailable;

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
        $this->vars['locales'] = Locale::listAvailable();
        $this->vars['field'] = $this->makeRenderFormField();
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
        $this->addCss('/plugins/rainlab/translate/assets/css/forms.css', 'RainLab.Translate');
    }

}