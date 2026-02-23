<?php namespace RainLab\Translate\Classes\EventRegistry;

use Event;
use Cms\Classes\Content;

/**
 * FormFieldHandler for backend form field translation events
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class FormFieldHandler
{
    /**
     * register events
     */
    public function register()
    {
        $this->extendBackendFormFields();
    }

    /**
     * boot events
     */
    public function boot()
    {
    }

    /**
     * extendBackendFormFields extends the backend form fields
     */
    protected function extendBackendFormFields()
    {
        // Defer event with low priority to let others contribute before this registers.
        Event::listen('backend.form.extendFieldsBefore', function($widget) {
            if ($widget->isNested) {
                return;
            }

            if (!$model = $widget->model) {
                return;
            }

            if (!method_exists($model, 'isClassExtendedWith')) {
                return;
            }

            if (
                !$model->isClassExtendedWith(\RainLab\Translate\Behaviors\TranslatableModel::class) &&
                !$model->isClassExtendedWith(\RainLab\Translate\Behaviors\TranslatablePage::class) &&
                !$model->isClassExtendedWith(\RainLab\Translate\Behaviors\TranslatableCmsObject::class)
            ) {
                return;
            }

            if (!$model->hasTranslatableAttributes()) {
                return;
            }

            if (!empty($widget->fields)) {
                $widget->fields = $this->processFormMLFields($widget->fields, $model);
            }

            if (!empty($widget->tabs['fields'])) {
                $widget->tabs['fields'] = $this->processFormMLFields($widget->tabs['fields'], $model);
            }

            if (!empty($widget->secondaryTabs['fields'])) {
                $widget->secondaryTabs['fields'] = $this->processFormMLFields($widget->secondaryTabs['fields'], $model);
            }
        }, -1);
    }

    /**
     * processFormMLFields function to flag multilingual fields as translatable
     * @param  array $fields
     * @param  Model $model
     * @return array
     */
    protected function processFormMLFields($fields, $model)
    {
        $translatable = array_flip($model->getTranslatableAttributes());

        // The CMS Content editor uses a form field named "markup_html" to render
        // the markup content. Map it here so the ML indicator appears on the field.
        if ($model instanceof Content && array_key_exists('markup', $translatable)) {
            $translatable['markup_html'] = true;
        }

        foreach ($fields as $name => $config) {
            if (!array_key_exists($name, $translatable)) {
                continue;
            }

            $fields[$name]['translatable'] = true;
        }

        return $fields;
    }
}
