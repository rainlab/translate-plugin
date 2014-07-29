<?php namespace RainLab\Translate\FormWidgets;

use RainLab\Translate\Models\Locale;

/**
 * ML Textarea
 * Renders a multi-lingual textarea field.
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class MLTextarea extends MLControl
{

    /**
     * {@inheritDoc}
     */
    public $defaultAlias = 'mltextarea';

    /**
     * {@inheritDoc}
     */
    public $fallbackType = 'textarea';

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $this->isAvailable = Locale::isAvailable();

        $this->prepareVars();

        if ($this->isAvailable)
            return $this->makePartial('mltextarea');
        else
            return parent::render();
    }

}