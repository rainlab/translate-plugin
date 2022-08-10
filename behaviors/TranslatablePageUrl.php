<?php namespace RainLab\Translate\Behaviors;

use App;
use RainLab\Translate\Classes\Translator;
use October\Rain\Extension\ExtensionBase;

/**
 * Translatable page URL model extension
 *
 * Usage:
 *
 * In the model class definition:
 *
 *   public $implement = ['@RainLab.Translate.Behaviors.TranslatablePageUrl'];
 *
 */
class TranslatablePageUrl extends ExtensionBase
{
    /**
     * @var \October\Rain\Database\Model Reference to the extended model.
     */
    protected $model;

    /**
     * @var string Active language for translations.
     */
    protected $translatableContext;

    /**
     * @var string Default system language.
     */
    protected $translatableDefault;

    /**
     * @var string Default page URL.
     */
    protected $translatableDefaultUrl;

    /**
     * __construct using the extended model.
     * @param \October\Rain\Database\Model $model
     */
    public function __construct($model)
    {
        $this->model = $model;

        $this->initTranslatableContext();

        $this->model->bindEvent('model.afterFetch', function() {
            $this->translatableDefaultUrl = $this->getModelUrl();

            if (!App::runningInBackend()) {
                $this->rewriteTranslatablePageUrl();
            }
        });
    }

    /**
     * initTranslatableContext, sets the default language code to use.
     * @return void
     */
    public function initTranslatableContext()
    {
        $translate = Translator::instance();
        $this->translatableContext = $translate->getLocale();
        $this->translatableDefault = $translate->getDefaultLocale();
    }

    /**
     * rewriteTranslatablePageUrl checks if a translated URL exists and rewrites it,
     * this method should only be called from the context of front-end.
     * @return void
     */
    public function rewriteTranslatablePageUrl($locale = null)
    {
        $locale = $locale ?: $this->translatableContext;
        $localeUrl = $this->translatableDefaultUrl;

        if ($locale !== $this->translatableDefault) {
            $localeUrl = $this->getSettingsUrlAttributeTranslated($locale) ?: $localeUrl;
        }

        $this->setModelUrl($localeUrl);
    }

    /**
     * hasTranslatablePageUrl determines if a locale has a translated URL.
     * @return bool
     */
    public function hasTranslatablePageUrl($locale = null)
    {
        $locale = $locale ?: $this->translatableContext;

        return strlen($this->getSettingsUrlAttributeTranslated($locale)) > 0;
    }

    /**
     * getSettingsUrlAttributeTranslated
     * @return string
     */
    public function getSettingsUrlAttributeTranslated($locale)
    {
        $defaults = ($locale == $this->translatableDefault) ? $this->translatableDefaultUrl : null;

        return array_get($this->model->attributes, 'viewBag.localeUrl.'.$locale, $defaults);
    }

    /**
     * setModelUrl
     */
    protected function setModelUrl($value)
    {
        if ($this->model instanceof \RainLab\Pages\Classes\Page) {
            array_set($this->model->attributes, 'viewBag.url', $value);
        }
        else {
            $this->model->url = $value;
        }
    }

    /**
     * getModelUrl
     */
    protected function getModelUrl()
    {
        if ($this->model instanceof \RainLab\Pages\Classes\Page) {
            return array_get($this->model->attributes, 'viewBag.url');
        }
        else {
            return $this->model->url;
        }
    }
}
