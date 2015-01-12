<?php namespace RainLab\Translate\FormWidgets;

use Backend\FormWidgets\RichEditor;
use RainLab\Translate\Models\Locale;

/**
 * ML Rich Editor
 * Renders a multi-lingual WYSIWYG edtiro.
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class MLRichEditor extends RichEditor
{

    /**
     * {@inheritDoc}
     */
    public $defaultAlias = 'mlricheditor';

    /**
     * @var boolean Determines whether translation services are available
     */
    public $isAvailable;

    public $originalAssetPath;
    public $originalViewPath;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        $this->isAvailable = Locale::isAvailable();

        parent::init();
    }

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $this->actAsParent();
        $parentContent = parent::render();
        $this->actAsParent(false);

        if (!$this->isAvailable)
            return $parentContent;

        return $parentContent.$this->makePartial('mlricheditor');
    }

    /**
     * {@inheritDoc}
     */
    public function loadAssets()
    {
        $this->actAsParent();
        parent::loadAssets();
        $this->actAsParent(false);

        if ($this->isAvailable) {
            $this->addJs('js/mlswitcher.js');
        }
    }

    protected function actAsParent($switch = true)
    {
        if ($switch) {
            $this->originalAssetPath = $this->assetPath;
            $this->originalViewPath = $this->viewPath;
            $this->assetPath = '/modules/backend/FormWidgets/richeditor/assets';
            $this->viewPath = PATH_BASE.'/modules/backend/FormWidgets/richeditor/partials';
        }
        else {
            $this->assetPath = $this->originalAssetPath;
            $this->viewPath = $this->originalViewPath;
        }
    }

}