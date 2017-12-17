<?php namespace RainLab\Translate\FormWidgets;

use Backend\FormWidgets\MediaFinder;
use RainLab\Translate\Models\Locale;
use System\Classes\MediaLibrary;

/**
 * MLMediaFinder Form Widget
 */
class MLMediaFinder extends MediaFinder
{
    use \RainLab\Translate\Traits\MLControl;

    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'mlmediafinder';
    public $originalAssetPath;
    public $originalViewPath;
    private $mediaPath;

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();
        $this->initLocale();
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->actAsParent();
        $parentContent = parent::render();
        $this->actAsParent(false);

        if (!$this->isAvailable) {
            return $parentContent;
        }

        $this->vars['mediafinder'] = $parentContent;
        return $this->makePartial('mlmediafinder');
    }

    /**
     * Prepares the form widget view data
     */
    public function prepareVars()
    {
        parent::prepareVars();
        $this->prepareLocaleVars();
        // make root path of media files accessible
        $this->vars['mediaPath'] = $this->mediaPath = MediaLibrary::url('/');
    }

    /**
     * @inheritDoc
     */
    public function loadAssets()
    {
        // we only load the css from parent, unfortunately the js must be reimplemented due to missing events.
        $this->addCss('/modules/backend/formwidgets/mediafinder/assets/css/mediafinder.css');
        $this->addJs('js/mlmediafinder.js');

        if (Locale::isAvailable()) {
            $this->loadLocaleAssets();
        }
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        return $this->getLocaleSaveValue($value);
    }

    protected function actAsParent($switch = true)
    {
        if ($switch) {
            $this->originalAssetPath = $this->assetPath;
            $this->originalViewPath = $this->viewPath;
            $this->assetPath = '/modules/backend/formwidgets/mediafinder/assets';
            $this->viewPath = base_path().'/modules/backend/formwidgets/mediafinder/partials';
        }
        else {
            $this->assetPath = $this->originalAssetPath;
            $this->viewPath = $this->originalViewPath;
        }
    }
}
