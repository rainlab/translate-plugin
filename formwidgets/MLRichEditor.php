<?php namespace RainLab\Translate\FormWidgets;

use RainLab\Translate\Models\Locale;
use Backend\FormWidgets\RichEditor;

/**
 * Rich Editor
 * Renders a multi-lingual rich content editor field.
 *
 * @package rainlab\translate
 * @author Modest Machnicki
 */
class MLRichEditor extends RichEditor
{
    use MLControl {
        MLControl::loadAssets as loadLocaleAssets;
    }

    /**
     * {@inheritDoc}
     */
    public $defaultAlias = 'mlricheditor';

    /**
     * {@inheritDoc}
     */
    public $fallbackType = 'richeditor';

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $this->isAvailable = Locale::isAvailable();

        $this->fullPage = $this->getConfig('fullPage', $this->fullPage);

        // RichEditor vars
        parent::prepareVars();

        // MLControl vars
        $this->prepareVars();

        if ($this->isAvailable)
            return $this->makePartial('mlricheditor');
        else
            return parent::render();
    }

    /**
     * {@inheritDoc}
     */
    public function loadAssets()
    {
        $this->assetPath = $this->guessViewPathFrom(parent::class, '/assets', true);

        parent::loadAssets();

        $this->loadLocaleAssets();

    }
}