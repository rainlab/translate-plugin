<?php namespace RainLab\Translate\Traits;

use Exception;
use RainLab\Translate\Classes\Translator;
use October\Rain\Database\Traits\Sluggable as BaseSluggable;

trait Sluggable
{
    use BaseSluggable {
        BaseSluggable::getSluggableUniqueAttributeValue as baseGetSluggableUniqueAttributeValue;
        BaseSluggable::newSluggableQuery as baseNewSluggableQuery;
    }

    /**
     * @var array List of attributes to automatically generate unique URL names (slugs) for.
     *
     * protected $slugs = [];
     */

    /**
     * @var bool Allow trashed slugs to be counted in the slug generation.
     *
     * protected $allowTrashedSlugs = false;
     */

     /** @var boolean Track the current locale being processed */
    protected static $currentLocale = false;

    /**
     * Boot the sluggable trait for a translatable model.
     *
     * @return void
     */
    public static function bootSluggable()
    {
        if (!property_exists(get_called_class(), 'slugs')) {
            throw new Exception(sprintf(
                'You must define a $slugs property in %s to use the Sluggable trait.',
                get_called_class()
            ));
        }

        /*
         * Set slugged attributes on new records and existing records if slug is missing.
         */
        static::extend(function ($model) {
            $model->bindEvent('model.saveInternal', function () use ($model) {
                $model->slugAttributes();
            });
        });

        static::extend(function ($model) {
            $model->bindEvent('model.translate.resolveComputedFields', function ($locale) use ($model) {
                self::$currentLocale = $locale;
                $attributes = $model->translateSlugAttributes($locale);
                self::$currentLocale = false;

                return $attributes;
            });
        });
    }

    /**
     * Processes and returns a list of translated slug attributes for a given locale.
     *
     * This method temporarily substitutes the model's attributes with the translated values in order to use the base
     * Sluggable trait's process. Once completed, it extracts the slugs from the model and then returns the model to
     * its original state.
     *
     * @param string $locale
     *
     * @return array
     */
    protected function translateSlugAttributes($locale)
    {
        $original = $this->getAttributes();

        // Move translated values to model attributes
        $this->translateContext($locale);
        $this->fill($this->getTranslateAttributes($locale));

        // Overwrite slug values with translated values in POST, if provided
        foreach (array_keys($this->slugs) as $slugAttribute) {
            if ($this->hasTranslation($slugAttribute, $locale)) {
                $this->{$slugAttribute} = $this->getAttributeTranslated($slugAttribute, $locale);
            } else {
                $this->{$slugAttribute} = null;
            }
        }

        // Generate slugs
        $this->slugAttributes();

        // Extract translated slugs and then return model to original state
        $translatedSlugs = [];
        foreach (array_keys($this->slugs) as $slugAttribute) {
            $translatedSlugs[$slugAttribute] = $this->{$slugAttribute};
        }

        $this->translateContext(Translator::instance()->getDefaultLocale());
        $this->fill($original);

        return $translatedSlugs;
    }

    /**
     * Ensures a unique attribute value, if the value is already used a counter suffix is added. Locale-aware.
     *
     * This will run the base Sluggable trait's method if the locale is the default locale.
     *
     * @param string $name The database column name.
     * @param value $value The desired column value.
     *
     * @return string A safe value that is unique.
     */
    protected function getSluggableUniqueAttributeValue($name, $value)
    {
        if (!self::$currentLocale) {
            return $this->baseGetSluggableUniqueAttributeValue($name, $value);
        }

        $counter = 1;
        $separator = $this->getSluggableSeparator();
        $_value = $value;

        while (($this->methodExists('withTrashed') && $this->allowTrashedSlugs) ?
            $this->newSluggableQuery()->transWhere($name, $_value, self::$currentLocale)->withTrashed()->count() > 0 :
            $this->newSluggableQuery()->transWhere($name, $_value, self::$currentLocale)->count() > 0
        ) {
            $counter++;
            $_value = $value . $separator . $counter;
        }

        return $_value;
    }

    /**
     * Returns a query that excludes the current record if it exists. Locale-aware.
     *
     * This will run the base Sluggable trait's method if the locale is the default locale.
     *
     * @return Builder
     */
    protected function newSluggableQuery()
    {
        if (!self::$currentLocale) {
            return $this->baseNewSluggableQuery();
        }

        return $this->exists
            ? $this->newQuery()->where($this->getTable() . '.' . $this->getKeyName(), '<>', $this->getKey())
            : $this->newQuery();
    }
}
