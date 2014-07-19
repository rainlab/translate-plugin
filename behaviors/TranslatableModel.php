<?php namespace RainLab\Translate\Behaviors;

use DB as Db;
use System\Classes\ModelBehavior;
use System\Classes\ApplicationException;
use RainLab\Translate\Classes\Translate;
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
 *   public $translatableFields = ['name', 'content'];
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
     * @var array Data store for translated attributes.
     */
    protected $translatableData = [];

    /**
     * {@inheritDoc}
     */
    protected $requiredProperties = ['translatableFields'];

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

        $this->model->bindEvent('model.saveInternal', function($data, $options) use ($model) {
            $this->syncTranslatableAttributes();
        });

    }

    /**
     * Initializes this class, sets the default language code to use.
     * @return void
     */
    public function initTranslatableContext()
    {
        $translate = Translate::instance();
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
        if ($this->translatableDefault == $this->translatableContext)
            return false;

        return in_array($key, $this->getTranslatableAttributes());
    }

    /**
     * Returns a translated attribute value.
     * @param  string $key
     * @return string
     */
    public function getTranslateAttribute($key, $locale = null)
    {
        if ($locale == null)
            $locale = $this->translatableContext;

        if ($locale == $this->translatableDefault)
            return $this->model->getAttributeValue($key);

        if (!array_key_exists($locale, $this->translatableData))
            $this->loadTranslatableData($locale);

        if (!isset($this->translatableData[$locale][$key]))
            return null;

        return $this->translatableData[$locale][$key];
    }

    /**
     * Sets a translated attribute value.
     * @param  string $key   Attribute
     * @param  string $value Value to translate
     * @return string        Translated value
     */
    public function setTranslateAttribute($key, $value, $locale = null)
    {
        if ($locale == null)
            $locale = $this->translatableContext;

        if ($locale == $this->translatableDefault)
            return $this->attributes[$key] = $value;

        if (!array_key_exists($locale, $this->translatableData))
            $this->loadTranslatableData($locale);

        return $this->translatableData[$locale][$key] = $value;
    }

    /**
     * Restores the default language values on the model and 
     * stores the translated values in the attributes table.
     * @return void
     */
    public function syncTranslatableAttributes()
    {
        if ($this->translatableContext == $this->translatableDefault)
            return;

        /*
         * Restore translatable values to models originals
         */
        $original = $this->model->getOriginal();
        $attributes = $this->model->getAttributes();
        $translatable = $this->model->getTranslatableAttributes();
        $originalValues = array_intersect_key($original, array_flip($translatable));
        $this->attributes = array_merge($attributes, $originalValues);

        /*
         * Store the translation data
         */
        $this->storeTranslatableData($this->translatableContext);
    }

    /**
     * Changes the active language for this model
     * @param  string $context
     * @return void
     */
    public function translateContext($context = null)
    {
        if ($context === null)
            return $this->translatableContext;

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
        return $this;
    }

    /**
     * Returns a collection of fields that will be hashed.
     * @return array
     */
    public function getTranslatableAttributes()
    {
        return $this->translatable;
    }

    /**
     * Saves the translation data in the join table.
     * @param  string $locale
     * @return void
     */
    protected function storeTranslatableData($locale = null)
    {
        if (!$locale)
            $locale = $this->translatableContext;

        if (!$this->model->exists)
            return;

        $data = json_encode($this->translatableData[$locale]);

        $obj = Db::table('rainlab_translate_attributes')
            ->where('locale', $locale)
            ->where('model_id', $this->model->getKey())
            ->where('model_type', get_class($this));

        if ($obj->count() > 0)
            return $obj->update(['attribute_data' => $data]);

        Db::table('rainlab_translate_attributes')->insert([
            'locale' => $locale,
            'model_id' => $this->model->getKey(),
            'model_type' => get_class($this),
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
        if (!$locale)
            $locale = $this->translatableContext;

        if (!$this->model->exists)
            return $this->translatableData[$locale] = [];

        $obj = Db::table('rainlab_translate_attributes')
            ->where('locale', $locale)
            ->where('model_id', $this->model->getKey())
            ->where('model_type', get_class($this))
            ->first();

        if (!$obj)
            return $this->translatableData[$locale] = [];

        return $this->translatableData[$locale] = json_decode($obj->attribute_data, true);
    }

}
