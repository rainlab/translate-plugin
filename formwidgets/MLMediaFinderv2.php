<?php namespace RainLab\Translate\FormWidgets;

use Media\Classes\MediaLibrary;
use Media\FormWidgets\MediaFinder;
use RainLab\Translate\Models\Locale;

/**
 * MLMediaFinderv2 renders a multilingual media finder for October CMS v2
 *
 * @package rainlab\translate
 * @author Sascha Aeppli
 */
class MLMediaFinderv2 extends MediaFinder
{
    use \RainLab\Translate\Traits\MLControl;

    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'mlmediafinder';

    /**
     * needed to preview images, because we only get a relative path
     * @var string path to media library
     */
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
    public function getSaveValue($value)
    {
        return $this->getLocaleSaveValue($value);
    }

    /**
     * @inheritDoc
     */
    public function loadAssets()
    {
        $this->actAsParent();
        parent::loadAssets();
        $this->actAsParent(false);

        if (Locale::isAvailable()) {
            $this->loadLocaleAssets();
            $this->addJs('../../mlmediafinder/assets/js/mlmediafinder.js');
            $this->addCss('../../mlmediafinder/assets/css/mlmediafinder.css');
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getParentViewPath()
    {
        return base_path().'/modules/media/formwidgets/mediafinder/partials';
    }

    /**
     * {@inheritDoc}
     */
    protected function getParentAssetPath()
    {
        return '/modules/media/formwidgets/mediafinder/assets';
    }
}
