<?php namespace RainLab\Translate\FormWidgets;

use RainLab\Blog\FormWidgets\BlogMarkdown;
use RainLab\Blog\Models\Post;
use RainLab\Translate\Models\Locale;

/**
 * ML Blog Markdown
 * Renders a multi-lingual text field.
 *
 * @property  originalViewPath
 * @package rainlab\translate
 * @author Rafał Soboń
 */
class MLBlogMarkdown extends BlogMarkdown
{

    use \RainLab\Translate\Traits\MLControl;

    /**
     * {@inheritDoc}
     */
    protected $defaultAlias = 'mlblogmarkdown';
    public $originalViewPath;
    public $originalAssetPath;


    public function init() {
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
        $this->overrideAssetPaths(true);
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
            $this->overrideAssetPaths(true);
            $this->addJs('js/mlmarkdowneditor.js');
            $this->overrideAssetPaths(false);
        }
    }

    public function getSaveValue($value)
    {
        $localeData = $this->getLocaleSaveData();

        /*
         * Set the translated values to the model
         */
        if ($this->model->methodExists('setTranslateAttribute')) {
            foreach ($localeData as $locale => $value) {
                $this->model->setTranslateAttribute($this->columnName, $value, $locale);
                $this->model->setTranslateAttribute('content_html', Post::formatHtml($value), $locale);

            }
        }

        return array_get($localeData, $this->defaultLocale->code, $value);
    }

    /*
     * This will override default october assetPath and viewPath which depends on the class name "mlblogmarkdown".
     * Intead we want the same assetPath and viewPath as normal "mlmarkdowneditor".
     */
    private function overrideAssetPaths($switch = true)
    {
        if ($switch) {
            $this->originalAssetPath = $this->assetPath;
            $this->originalViewPath = $this->viewPath;
            $this->assetPath = "/plugins/rainlab/translate/formwidgets/mlmarkdowneditor/assets";
            $this->viewPath = base_path()."/plugins/rainlab/translate/formwidgets/mlmarkdowneditor/partials";
        }
        else {
            $this->assetPath = $this->originalAssetPath;
            $this->viewPath = $this->originalViewPath;
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
