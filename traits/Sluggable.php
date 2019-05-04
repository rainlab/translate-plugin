<?php namespace RainLab\Translate\Traits;

use Str;

trait Sluggable
{
    /**
     * Adds translatable slug attributes to the translation data
     * @return void
     */
    protected function slugAttributesTranslated($locale)
    {
        if (!isset($this->model->slugs) || !method_exists($this->model, 'slugAttributes')) return;
        
        $optionedAttributes = $this->getTranslatableAttributesWithOptions();

        foreach ($this->model->slugs as $slugAttribute => $sourceAttributes) {
            if (!isset($optionedAttributes[$slugAttribute])) continue;
            $this->setSluggedValueTranslated($locale, $slugAttribute, $sourceAttributes);
        }
    }

    /**
     * Sets a single translatable slug attribute value.
     * @param string $locale
     * @param string $slugAttribute Attribute to populate with the slug.
     * @param mixed $sourceAttributes Attribute(s) to generate the slug from.
     * Supports dotted notation for relations.
     * @param int $maxLength Maximum length for the slug not including the counter.
     * @return string The generated value.
     */
    protected function setSluggedValueTranslated($locale, $slugAttribute, $sourceAttributes, $maxLength = 175)
    {
        if (!isset($this->translatableAttributes[$locale][$slugAttribute]) || !mb_strlen($this->translatableAttributes[$locale][$slugAttribute])) {
            if (!is_array($sourceAttributes)) {
                $sourceAttributes = [$sourceAttributes];
            }

            $slugArr = [];
            foreach ($sourceAttributes as $attribute) {
                $slugArr[] = $this->getSluggableSourceAttributeValueTranslated($locale, $attribute);
            }

            $slug = implode(' ', $slugArr);
            $slug = mb_substr($slug, 0, $maxLength);
            $slug = Str::slug($slug, $this->model->getSluggableSeparator());
        }
        else {
            $slug = $this->translatableAttributes[$locale][$slugAttribute];
        }

        return $this->translatableAttributes[$locale][$slugAttribute] = $this->getSluggableUniqueAttributeValueTranslated($locale, $slugAttribute, $slug);
    }

    /**
     * Ensures a unique attribute value for the locale, if the value is already used a counter suffix is added.
     * @param string $locale
     * @param string $name The database column name.
     * @param value $value The desired column value.
     * @return string A safe value that is unique.
     */
    protected function getSluggableUniqueAttributeValueTranslated($locale, $name, $value)
    {
        $counter = 1;
        $separator = $this->model->getSluggableSeparator();
        $_value = $value;

        while (($this->model->methodExists('withTrashed') && $this->model->allowTrashedSlugs) ?
            $this->newSluggableQueryTranslated()->transWhere($name, $_value, $locale)->withTrashed()->count() > 0 :
            $this->newSluggableQueryTranslated()->transWhere($name, $_value, $locale)->count() > 0
        ) {
            $counter++;
            $_value = $value . $separator . $counter;
        }

        return $_value;
    }

    /**
     * Returns a query that excludes the current record if it exists
     * @return Builder
     */
    protected function newSluggableQueryTranslated()
    {
        return $this->model->exists
            ? $this->model->newQuery()->where($this->model->getTable().'.'.$this->model->getKeyName(), '<>', $this->model->getKey())
            : $this->model->newQuery();
    }

    /**
     * Get an attribute relation value using dotted notation.
     * Eg: author.name
     * @return mixed
     */
    protected function getSluggableSourceAttributeValueTranslated($locale, $key)
    {
        if (strpos($key, '.') === false) {
            return $this->translatableAttributes[$locale][$key];
        }

        $keyParts = explode('.', $key);
        $value = $this->model;
        foreach ($keyParts as $part) {
            if (!isset($value[$part])) {
                return null;
            }

            $value = $value[$part];
        }

        return $value;
    }
}
