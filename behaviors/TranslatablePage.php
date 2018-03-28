<?php namespace RainLab\Translate\Behaviors;

use App;
use RainLab\Translate\Classes\Translator;
use October\Rain\Extension\ExtensionBase;
use ApplicationException;
use Exception;

/**
 * Translatable Page Model extension
 *
 * Usage:
 *
 * In the model class definition:
 *
 *   public $implement = ['@RainLab.Translate.Behaviors.TranslatablePage'];
 *
 */
class TranslatablePage extends ExtensionBase
{
    protected $model;

    protected $attributes = ['url', 'title', 'description', 'meta_title', 'meta_description'];

    protected $translatableUseFallback = true;

    protected $translatableContext;

    protected $translatableDefault;

    /**
     * @var string Default Page Attributes.
     */
    protected $translatableDefaultAttributes = [];

    public function __construct($model)
    {
        $this->model = $model;

        $this->initTranslatableContext();

        $this->model->bindEvent('model.afterFetch', function() {
            $this->translatableDefaultAttributes = $this->getModelAttributes();

            if (!App::runningInBackend()) {
                $this->rewriteTranslatablePageAttributes();
            }
        });
    }

    protected function setModelAttribute($attr, $value)
    {
        $this->model[$attr] = $value;
    }

    protected function getModelAttributes()
    {
        $attributes = [];
        foreach ($this->attributes as $attr) {
            $attributes[$attr] = $this->model[$attr];
        }
        return $attributes;
    }

    /**
     * Initializes this class, sets the default language code to use.
     * @return void
     */
    public function initTranslatableContext()
    {
        $translate = Translator::instance();
        $this->translatableContext = $translate->getLocale();
        $this->translatableDefault = $translate->getDefaultLocale();
    }

    public function noFallbackLocale()
    {
        $this->translatableUseFallback = false;

        return $this->model;
    }

    /**
     * Checks if a translated Attribute exists and rewrites it.
     * This method should only be called from the context of front-end.
     * @return void
     */
    public function rewriteTranslatablePageAttributes($locale = null)
    {
        $locale = $locale ?: $this->translatableContext;

        foreach ($this->attributes as $attr) {
            $locale_attr = $this->translatableDefaultAttributes[$attr];

            if ($locale != $this->translatableDefault) {
                $locale_attr = $this->getAttributeTranslated($attr, $locale) ?: $locale_attr;
            }

            $this->setModelAttribute($attr, $locale_attr);
        }
    }

    /**
     * Checks if a translated URL exists and rewrites it.
     * This method should only be called from the context of front-end.
     * @return void
     */
    public function rewriteTranslatablePageUrl($locale = null)
    {
        $locale = $locale ?: $this->translatableContext;
        $localeUrl = $this->translatableDefaultAttributes['url'];

        if ($locale != $this->translatableDefault) {
            $localeUrl = $this->getAttributeTranslated('url', $locale) ?: $localeUrl;
        }

        $this->setModelAttribute('url', $localeUrl);
    }

    /*
     * Called by MLControl to get a translated page attribute
     */
    public function getAttributeTranslated($attr, $locale)
    {
        if (strpos($attr, 'settings[') === 0)
            $attr = preg_split("/[\[\]]/", $attr)[1];

        $defaults = ($locale == $this->translatableDefault) ? $this->translatableDefaultAttributes[$attr] : null;

        $locale_attr = sprintf('viewBag.locale%s.%s', ucfirst($attr), $locale);
        return array_get($this->model->attributes, $locale_attr, $defaults);
    }

    /*
     * Called by MLControl to set a translated page attribute
     */
    public function setAttributeTranslated($attr, $value, $locale)
    {
        if ($locale == $this->translatableDefault) {
            return;
        }

        if (strpos($attr, 'settings[') === 0)
            $attr = preg_split("/[\[\]]/", $attr)[1];

        if ($value == $this->translatableDefaultAttributes[$attr]) {
            return;
        }

        /*
         * The CMS controller will purge attributes just before saving, this
         * will ensure the attributes are injected after this logic.
         */
        $this->model->bindEventOnce('model.beforeSave', function() use ($attr, $value, $locale) {
            $locale_attr = sprintf('viewBag.locale%s.%s', ucfirst($attr), $locale);
            if (!$value) {
                array_forget($this->model->attributes, $locale_attr);
            }
            else {
                array_set($this->model->attributes, $locale_attr, $value);
            }
        });
    }

    /**
     * Mutator detected by MLControl, proxy for Static Pages plugin.
     * @return string
     */
    public function getViewBagUrlAttributeTranslated($locale)
    {
        return $this->getAttributeTranslated('url', $locale);
    }

    /**
     * Mutator detected by MLControl, proxy for Static Pages plugin.
     * @return void
     */
    public function setViewBagUrlAttributeTranslated($value, $locale)
    {
        $this->setAttributeTranslated('url', $value, $locale);
    }
}
