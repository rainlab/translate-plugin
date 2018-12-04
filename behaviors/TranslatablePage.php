<?php namespace RainLab\Translate\Behaviors;

use RainLab\Translate\Classes\TranslatableBehavior;

/**
 * Translatable page model extension
 *
 * Usage:
 *
 * In the model class definition:
 *
 *   public $implement = ['@RainLab.Translate.Behaviors.TranslatablePage'];
 *
 *   public $translatable = ['title', 'description'];
 *
 */
class TranslatablePage extends TranslatableBehavior
{
    protected $translatable = [];

    public function __construct($model)
    {
        parent::__construct($model);

        if (isset($this->model->translatable)) {
            $this->translatable = $this->model->translatable;
        }

        $this->model->bindEvent('model.afterFetch', function() {
            $this->translatableOriginals = $this->getModelAttributes();

            if ( ! app()->runningInBackend()) {
                $this->rewriteTranslatablePageAttributes();
            }
        });
    }

    public function getTranslatableAttributes()
    {
        $attributes = [];

        foreach ($this->translatable as $attr) {
            $attributes[] = "settings[{$attr}]";
        }
        return $attributes;
    }

    public function getModelAttributes()
    {
        $attributes = [];

        foreach ($this->translatable as $attr) {
            $attributes[$attr] = $this->model[$attr];
        }
        return $attributes;
    }

    public function initTranslatableContext()
    {
        parent::initTranslatableContext();
        $this->translatableOriginals = $this->getModelAttributes();
    }

    public function rewriteTranslatablePageAttributes($locale = null)
    {
        $locale = $locale ?: $this->translatableContext;

        foreach ($this->translatable as $attr) {
            $locale_attr = $this->translatableOriginals[$attr];

            if ($locale != $this->translatableDefault) {
                $locale_attr = $this->getAttributeTranslated($attr, $locale) ?: $locale_attr;
            }

            $this->model[$attr] = $locale_attr;
        }
    }

    public function getAttributeTranslated($key, $locale = null)
    {
        if (strpbrk($key, '[]') !== false) {
            // retrieve attr name within brackets (i.e. settings[title] yields title)
            $key = preg_split("/[\[\]]/", $key)[1];
        }

        $defaults = ($locale == $this->translatableDefault) ? $this->translatableOriginals[$key] : null;

        $locale_attr = sprintf('viewBag.locale%s.%s', ucfirst($key), $locale);
        return array_get($this->model->attributes, $locale_attr, $defaults);
    }

    public function setAttributeTranslated($key, $value, $locale = null)
    {
        if ($locale == $this->translatableDefault) {
            return;
        }

        if (strpbrk($key, '[]') !== false) {
            // retrieve attr name within brackets (i.e. settings[title] yields title)
            $key = preg_split("/[\[\]]/", $key)[1];
        }

        if ($value == $this->translatableOriginals[$key]) {
            return;
        }

        $this->model->bindEventOnce('model.beforeSave', function() use ($key, $value, $locale) {
            $locale_attr = sprintf('viewBag.locale%s.%s', ucfirst($key), $locale);
            if (!$value) {
                array_forget($this->model->attributes, $locale_attr);
            }
            else {
                array_set($this->model->attributes, $locale_attr, $value);
            }
        });
    }

    public function hasTranslatableAttributes()
    {
        return !empty($this->translatable);
    }

    // not needed but parent abstract model requires those
    protected function storeTranslatableData($locale = null) {}
    protected function loadTranslatableData($locale = null) {}
}
