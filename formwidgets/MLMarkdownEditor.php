<?php namespace RainLab\Translate\FormWidgets;

use Backend\FormWidgets\MarkdownEditor;
use RainLab\Translate\Models\Locale;

/**
 * ML Blog Markdown
 * Renders a multi-lingual text field.
 *
 * @property  originalViewPath
 * @package rainlab\translate
 * @author Rafał Soboń
 */
class MLMarkdownEditor extends MarkdownEditor
{
    use \RainLab\Translate\Traits\MLControl;

    /**
     * {@inheritDoc}
     */
    protected $defaultAlias = 'mlmarkdowneditor';
    public $originalViewPath;
    public $originalAssetPath;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        $this->actAsParent();
        parent::init();
        $this->actAsParent(false);
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

        $this->vars['markdowneditor'] = $parentContent;
        return $this->makePartial('mlmarkdowneditor');
    }

    public function prepareVars()
    {
        parent::prepareVars();
        $this->prepareLocaleVars();
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
            $this->addJs('js/mlmarkdowneditor.js');
        }
    }

    private function actAsParent($switch = true)
    {
        if ($switch) {
            $this->originalAssetPath = $this->assetPath;
            $this->originalViewPath = $this->viewPath;
            $this->assetPath = '/modules/backend/formwidgets/markdowneditor/assets';
            $this->viewPath = base_path().'/modules/backend/formwidgets/markdowneditor/partials';
        }
        else {
            $this->assetPath = $this->originalAssetPath;
            $this->viewPath = $this->originalViewPath;
        }
    }

}
