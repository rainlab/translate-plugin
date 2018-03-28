<?php namespace RainLab\Translate\Behaviors;

use App;
use RainLab\Translate\Classes\Translator;
use October\Rain\Extension\ExtensionBase;
use ApplicationException;
use Exception;

/**
 * Translatable page model extension
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
    /**
     * @var \October\Rain\Database\Model Reference to the extended model.
     */
    protected $model;

    protected $attributes = ['title', 'description', 'meta_title', 'meta_description'];

    /**
     * @var string Active language for translations.
     */
    protected $translatableContext;

    /**
     * @var string Default system language.
     */
    protected $translatableDefault;

    /**
     * @var string Default page Attributes.
     */
    protected $translatableDefaultAttributes = [];

    /**
     * Constructor
     * @param \October\Rain\Database\Model $model The extended model.
     */
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

    protected function getModelAttribute($attr)
    {
        return $this->model[$attr];
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

    /**
     * Determines if a locale has a translated URL.
     * @return bool
     */
    public function hasTranslatablePageAttribute($attr, $locale = null)
    {
        $locale = $locale ?: $this->translatableContext;

        return strlen($this->getSettingsAttributeTranslated($attr, $locale)) > 0;
    }

    /**
     * Mutator detected by MLControl
     * @return string
     */
    public function getSettingsAttributeTranslated($attr, $locale)
    {
        $defaults = ($locale == $this->translatableDefault) ? $this->translatableDefaultAttributes[$attr] : null;

        $locale_attr = sprintf('viewBag.locale%s.%s', ucfirst($attr), $locale);
        return array_get($this->model->attributes, $locale_attr, $defaults);
    }

    /**
     * Mutator detected by MLControl
     * @return void
     */
    public function setSettingsAttributeTranslated($attr, $value, $locale)
    {
        if ($locale == $this->translatableDefault) {
            return;
        }

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
     * Checks if a translated URL exists and rewrites it, this method
     * should only be called from the context of front-end.
     * @return void
     */
    public function rewriteTranslatablePageAttributes($locale = null)
    {
        $locale = $locale ?: $this->translatableContext;

        foreach ($this->attributes as $attr) {
            $locale_attr = $this->translatableDefaultAttributes[$attr];

            if ($locale != $this->translatableDefault) {
                $locale_attr = $this->getSettingsAttributeTranslated($attr, $locale) ?: $locale_attr;
            }

            $this->setModelAttribute($locale_attr);
        }
    }

    /**
     * Mutator detected by MLControl, proxy for Static Pages plugin.
     * @return string
     */
    public function getViewBagAttributeTranslated($attr, $locale)
    {
        return $this->getSettingsAttributeTranslated($attr, $locale);
    }

    /**
     * Mutator detected by MLControl, proxy for Static Pages plugin.
     * @return void
     */
    public function setViewBagAttributeTranslated($attr, $value, $locale)
    {
        $this->setSettingsAttributeTranslated($attr, $value, $locale);
    }
}
