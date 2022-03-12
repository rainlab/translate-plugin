<?php namespace RainLab\Translate\FormWidgets;

use Backend\FormWidgets\RichEditor;
use RainLab\Translate\Models\Locale;

/**
 * MLRichEditor renders a multi-lingual WYSIWYG editor.
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class MLRichEditor extends RichEditor
{
    use \RainLab\Translate\Traits\MLControl;

    /**
     * {@inheritDoc}
     */
    protected $defaultAlias = 'mlricheditor';

    /**
     * @var string originalAssetPath
     */
    public $originalAssetPath;

    /**
     * @var string originalViewPath
     */
    public $originalViewPath;

    /**
     * @var bool legacyMode disables the Vue integration
     */
    public $legacyMode = true;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        parent::init();
        $this->initLocale();
    }

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $this->actAsParent();
        $parentContent = parent::render();
        $this->actAsParent(false);

        if (!$this->isAvailable) {
            return $parentContent;
        }

        $this->vars['richeditor'] = $parentContent;
        return $this->makePartial('mlricheditor');
    }

    /**
     * prepareVars
     */
    public function prepareVars()
    {
        parent::prepareVars();
        $this->prepareLocaleVars();
    }

    /**
     * getSaveValue returns an array of translated values for this field
     * @return array
     */
    public function getSaveValue($value)
    {
        return $this->getLocaleSaveValue($value);
    }

    /**
     * {@inheritDoc}
     */
    protected function loadAssets()
    {
        $this->actAsParent();
        parent::loadAssets();
        $this->actAsParent(false);

        if (Locale::isAvailable()) {
            $this->loadLocaleAssets();
            $this->addJs('js/mlricheditor.js');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function onLoadPageLinksForm()
    {
        $this->actAsParent();
        return parent::onLoadPageLinksForm();
    }

    /**
     * {@inheritDoc}
     */
    protected function getParentViewPath()
    {
        return base_path().'/modules/backend/formwidgets/richeditor/partials';
    }

    /**
     * {@inheritDoc}
     */
    protected function getParentAssetPath()
    {
        return '/modules/backend/formwidgets/richeditor/assets';
    }
}
