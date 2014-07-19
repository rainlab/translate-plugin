<?php namespace RainLab\Translate\FormWidgets;

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
     * @var boolean Determines whether content has HEAD and HTML tags.
     */
    // public $fullPage = false;

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        // $this->fullPage = $this->getConfig('fullPage', $this->fullPage);

        $this->prepareVars();
        return $this->makePartial('mltext');
    }

    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        // $this->vars['fullPage'] = $this->fullPage;
        $this->vars['field'] = $this->formField;
    }

    /**
     * {@inheritDoc}
     */
    public function loadAssets()
    {
        $this->addCss('/plugins/rainlab/translate/assets/css/forms.css', 'RainLab.Translate');
        // $this->addCss('vendor/redactor/redactor.css', 'core');
        // $this->addCss('css/richeditor.css', 'core');
        // $this->addJs('vendor/redactor/redactor.js', 'core');
        // $this->addJs('js/richeditor.js', 'core');
    }

}