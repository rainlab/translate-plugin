<?php namespace RainLab\Translate\Behaviors;

use RainLab\Translate\Classes\MLCmsObject;
use RainLab\Translate\Classes\Translator;
use RainLab\Translate\Classes\TranslatableBehavior;
use October\Rain\Html\Helper as HtmlHelper;

/**
 * Translatable CMS Object extension
 *
 * Usage:
 *
 * In the CMS object class definition:
 *
 *   public $implement = ['@RainLab.Translate.Behaviors.TranslatableCmsObject'];
 *
 *   public $translatable = ['title', 'markup'];
 *
 */
class TranslatableCmsObject extends TranslatableBehavior
{

    /**
     * @var array Data store for translated viewbag attributes.
     */
    protected $translatableViewBag = [];

    /**
     * Constructor
     * @param \October\Rain\Database\Model $model The extended model.
     */
    public function __construct($model)
    {
        parent::__construct($model);

        $this->model->bindEvent('model.afterFetch', function() {
            $this->mergeViewBagAttributes();
        });
    }

    // @todo This needs work
    protected function mergeViewBagAttributes()
    {
        $locale = $this->translatableContext;

        if (!array_key_exists($locale, $this->translatableAttributes)) {
            $this->loadTranslatableData($locale);
        }

        if (isset($this->translatableViewBag[$locale])) {
            $this->model->viewBag = array_merge(
                $this->model->viewBag,
                $this->translatableViewBag[$locale]
            );
        }
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

        $data = $this->translatableAttributes[$locale];

        if (!$obj = $this->getCmsObjectForLocale($locale)) {
            $obj = MLCmsObject::forLocale($locale, $this->model);
            $obj->fileName = $this->model->fileName;
        }

        $obj->fill($data);
        $obj->forceSave();
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

        $obj = $this->getCmsObjectForLocale($locale);

        $result = $obj ? $obj->getAttributes() : [];

        $this->translatableViewBag[$locale] = $obj ? $obj->viewBag : [];

        return $this->translatableOriginals[$locale] = $this->translatableAttributes[$locale] = $result;
    }

    protected function getCmsObjectForLocale($locale)
    {
        if ($locale == $this->translatableDefault) {
            return $this->model;
        }

        return MLCmsObject::findLocale($locale, $this->model);
    }

}