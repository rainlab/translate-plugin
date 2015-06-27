<?php namespace RainLab\Translate\Behaviors;

use Db;
use System\Classes\ModelBehavior;
use ApplicationException;
use RainLab\Translate\Classes\Translator;
use Exception;

/**
 * Translatable model extension
 *
 * Usage:
 *
 * In the model class definition:
 *
 *   public $implement = ['RainLab.Translate.Behaviors.TranslatableModel'];
 *
 *   public $translatable = ['name', 'content'];
 *
 */
class TranslatableModel extends ModelBehavior
{

    /**
     * @var string Active language for translations.
     */
    protected $translatableContext;

    /**
     * @var string Active language for translations.
     */
    protected $translatableDefault;
    
    /**
     * @var bool Determines if empty translations should be replaced by default values.
     */
    protected $translatableUseFallback = true;

    /**
     * @var array Data store for translated attributes.
     */
    protected $translatableAttributes = [];

    /**
     * @var array Data store for original translated attributes.
     */
    protected $translatableOriginals = [];

    /**
     * {@inheritDoc}
     */
    protected $requiredProperties = ['translatable'];

    /**
     * Constructor
     */
    public function __construct($model)
    {
        parent::__construct($model);

        $this->initTranslatableContext();

        $this->model->bindEvent('model.beforeGetAttribute', function($key) use ($model) {
            if ($this->isTranslatable($key))
                return $this->getTranslateAttribute($key);
        });

        $this->model->bindEvent('model.beforeSetAttribute', function($key, $value) use ($model) {
            if ($this->isTranslatable($key))
                return $this->setTranslateAttribute($key, $value);
        });

        $this->model->bindEvent('model.saveInternal', function() use ($model) {
            $this->syncTranslatableAttributes();
        });

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
     * Checks if an attribute should be translated or not.
     * @param  string  $key
     * @return boolean
     */
    public function isTranslatable($key)
    {
        if ($this->translatableDefault == $this->translatableContext) {
            return false;
        }

        return in_array($key, $this->model->getTranslatableAttributes());
    }

    /**
     * Disables translation fallback locale.
     * @return self
     */
    public function noFallbackLocale()
    {
        $this->translatableUseFallback = false;
        return $this->model;
    }

    /**
     * Returns a translated attribute value.
     * @param  string $key
     * @return string
     */
    public function getTranslateAttribute($key, $locale = null)
    {
        if ($locale == null) {
            $locale = $this->translatableContext;
        }

        if ($locale == $this->translatableDefault) {
            return $this->model->getAttributeValue($key);
        }

        if (!array_key_exists($locale, $this->translatableAttributes)) {
            $this->loadTranslatableData($locale);
        }

        if ($this->hasTranslation($key, $locale)) {
            return $this->translatableAttributes[$locale][$key];
        }

        if ($this->translatableUseFallback) {
            return $this->model->getAttributeValue($key);
        }

        return null;
    }

    /**
     * Returns whether the attribute is translatable (has a translation) for the given locale.
     * @param  string $key
     * @param  string $locale
     * @return bool
     */
    public function hasTranslation($key, $locale)
    {
        return !empty($this->translatableAttributes[$locale][$key]);
    }

    /**
     * Sets a translated attribute value.
     * @param  string $key   Attribute
     * @param  string $value Value to translate
     * @return string        Translated value
     */
    public function setTranslateAttribute($key, $value, $locale = null)
    {
        if ($locale == null) {
            $locale = $this->translatableContext;
        }

        if ($locale == $this->translatableDefault) {
            return $this->attributes[$key] = $value;
        }

        if (!array_key_exists($locale, $this->translatableAttributes)) {
            $this->loadTranslatableData($locale);
        }

        return $this->translatableAttributes[$locale][$key] = $value;
    }

    /**
     * Restores the default language values on the model and 
     * stores the translated values in the attributes table.
     * @return void
     */
    public function syncTranslatableAttributes()
    {
        /*
         * Spin through the known locales, store the translations if necessary
         */
        $knownLocales = array_keys($this->translatableAttributes);
        foreach ($knownLocales as $locale) {
            if (!$this->isTranslateDirty(null, $locale))
                continue;

            $this->storeTranslatableData($locale);
        }

        /*
         * Saving the default locale, no need to restore anything
         */
        if ($this->translatableContext == $this->translatableDefault) return;

        /*
         * Restore translatable values to models originals
         */
        $original = $this->model->getOriginal();
        $attributes = $this->model->getAttributes();
        $translatable = $this->model->getTranslatableAttributes();
        $originalValues = array_intersect_key($original, array_flip($translatable));
        $this->attributes = array_merge($attributes, $originalValues);
    }

    /**
     * Changes the active language for this model
     * @param  string $context
     * @return void
     */
    public function translateContext($context = null)
    {
        if ($context === null) {
            return $this->translatableContext;
        }

        $this->translatableContext = $context;
    }

    /**
     * Shorthand for translateContext method, and chainable.
     * @param  string $context
     * @return self
     */
    public function lang($context = null)
    {
        $this->translateContext($context);
        return $this->model;
    }

    /**
     * Returns a collection of fields that will be hashed.
     * @return array
     */
    public function getTranslatableAttributes()
    {
        return $this->model->translatable;
    }

    /**
     * Saves the translation data in the join table.
     * @param  string $locale
     * @return void
     */
    protected function storeTranslatableData($locale = null)
    {
        if (!$locale) {
            $locale = $this->translatableContext;
        }

        /*
         * Model doesn't exist yet, defer this logic in memory
         */
        if (!$this->model->exists) {
            $this->model->bindEventOnce('model.afterCreate', function() use ($locale) {
                $this->storeTranslatableData($locale);
            });
            return;
        }

        $data = json_encode($this->translatableAttributes[$locale]);

        $obj = Db::table('rainlab_translate_attributes')
            ->where('locale', $locale)
            ->where('model_id', $this->model->getKey())
            ->where('model_type', get_class($this->model));

        if ($obj->count() > 0) {
            return $obj->update(['attribute_data' => $data]);
        }

        Db::table('rainlab_translate_attributes')->insert([
            'locale' => $locale,
            'model_id' => $this->model->getKey(),
            'model_type' => get_class($this->model),
            'attribute_data' => $data
        ]);
    }

    /**
     * Loads the translation data from the join table.
     * @param  string $locale
     * @return array
     */
    protected function loadTranslatableData($locale = null)
    {
        if (!$locale) {
            $locale = $this->translatableContext;
        }

        if (!$this->model->exists) {
            return $this->translatableAttributes[$locale] = [];
        }

        $obj = Db::table('rainlab_translate_attributes')
            ->where('locale', $locale)
            ->where('model_id', $this->model->getKey())
            ->where('model_type', get_class($this->model))
            ->first();

        $result = ($obj) ? json_decode($obj->attribute_data, true) : [];

        return $this->translatableOriginals[$locale] = $this->translatableAttributes[$locale] = $result;
    }

    /**
     * Determine if the model or a given translated attribute has been modified.
     * @param  string|null  $attribute
     * @return bool
     */
    public function isTranslateDirty($attribute = null, $locale = null)
    {
        $dirty = $this->getTranslateDirty($locale);

        if (is_null($attribute)) {
            return count($dirty) > 0;
        }
        else {
            return array_key_exists($attribute, $dirty);
        }
    }

    /**
     * Get the translated attributes that have been changed since last sync.
     * @return array
     */
    public function getTranslateDirty($locale = null)
    {
        if (!$locale) {
            $locale = $this->translatableContext;
        }

        if (!array_key_exists($locale, $this->translatableAttributes)) {
            return [];
        }

        if (!array_key_exists($locale, $this->translatableOriginals)) {
            return $this->translatableAttributes[$locale]; // All dirty
        }

        $dirty = [];

        foreach ($this->translatableAttributes[$locale] as $key => $value) {

            if (!array_key_exists($key, $this->translatableOriginals[$locale])) {
                $dirty[$key] = $value;
            }
            elseif ($value != $this->translatableOriginals[$locale][$key]) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

}
