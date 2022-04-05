<?php namespace RainLab\Translate\Traits;

use Str;
use RainLab\Translate\Models\Locale;
use October\Rain\Html\Helper as HtmlHelper;

/**
 * MLControl is a generic ML Control for rendering a multi-lingual control.
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
trait MLControl
{
    /**
     * @var boolean isAvailable determines whether translation services are available
     */
    public $isAvailable;

    /**
     * @var string originalAssetPath stores the original asset path when acting as the parent control
     */
    public $originalAssetPath;

    /**
     * @var string originalViewPath stores the original view path when acting as the parent control
     */
    public $originalViewPath;

    /**
     * @var RainLab\Translate\Models\Locale defaultLocale object
     */
    protected $defaultLocale;

    /**
     * initLocale initializes the control
     * @return void
     */
    public function initLocale()
    {
        $this->defaultLocale = Locale::getDefault();
        $this->isAvailable = Locale::isAvailable();
    }

    /**
     * getParentViewPath returns the parent control's view path
     *
     * @return string
     */
    protected function getParentViewPath()
    {
        // return base_path().'/modules/backend/formwidgets/parentcontrol/partials';
    }

    /**
     * getParentAssetPath returns the parent control's asset path
     *
     * @return string
     */
    protected function getParentAssetPath()
    {
        // return '/modules/backend/formwidgets/parentcontrol/assets';
    }

    /**
     * actAsParent swaps the asset & view paths with the parent control's to
     * act as the parent control
     *
     * @param boolean $switch Defaults to true, determines whether to act as the parent or revert to current
     */
    protected function actAsParent($switch = true)
    {
        if ($switch) {
            $this->originalAssetPath = $this->assetPath;
            $this->originalViewPath = $this->viewPath;
            $this->assetPath = $this->getParentAssetPath();
            $this->viewPath = $this->getParentViewPath();
        }
        else {
            $this->assetPath = $this->originalAssetPath;
            $this->viewPath = $this->originalViewPath;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function renderFallbackField()
    {
        return $this->makeMLPartial('fallback_field');
    }

    /**
     * makeMLPartial is used by child classes to render in context of this view path.
     * @param string $partial The view to load.
     * @param array $params Parameter variables to pass to the view.
     * @return string The view contents.
     */
    public function makeMLPartial($partial, $params = [])
    {
        $oldViewPath = $this->viewPath;
        $this->viewPath = $this->guessViewPathFrom(__TRAIT__, '/partials');
        $result = $this->makePartial($partial, $params);
        $this->viewPath = $oldViewPath;

        return $result;
    }

    /**
     * prepareLocaleVars prepares the list data
     */
    public function prepareLocaleVars()
    {
        $this->vars['defaultLocale'] = $this->defaultLocale;
        $this->vars['locales'] = Locale::listAvailable();
        $this->vars['field'] = $this->makeRenderFormField();
    }

    /**
     * loadLocaleAssets loads assets specific to ML Controls
     */
    public function loadLocaleAssets()
    {
        $this->addJs('/plugins/rainlab/translate/assets/js/multilingual.js', 'RainLab.Translate');
        $this->addCss('/plugins/rainlab/translate/assets/css/multilingual.css', 'RainLab.Translate');

        if (!class_exists('System')) {
            $this->addCss('/plugins/rainlab/translate/assets/css/multilingual-v1.css', 'RainLab.Translate');
        }
    }

    /**
     * getLocaleValue returns a translated value for a given locale.
     * @param  string $locale
     * @return string
     */
    public function getLocaleValue($locale)
    {
        $key = $this->valueFrom ?: $this->fieldName;

        /*
         * Get the translated values from the model
         */
        $studKey = Str::studly(implode(' ', HtmlHelper::nameToArray($key)));
        $mutateMethod = 'get'.$studKey.'AttributeTranslated';

        if ($this->objectMethodExists($this->model, $mutateMethod)) {
            $value = $this->model->$mutateMethod($locale);
        }
        elseif ($this->objectMethodExists($this->model, 'getAttributeTranslated') && $this->defaultLocale->code != $locale) {
            $value = $this->model->noFallbackLocale()->getAttributeTranslated($key, $locale);
        }
        else {
            $value = $this->formField->value;
        }

        return $value;
    }

    /**
     * makeRenderFormField if translation is unavailable, render the original field type (text).
     */
    protected function makeRenderFormField()
    {
        if ($this->isAvailable) {
            return $this->formField;
        }

        $field = clone $this->formField;
        $field->type = $this->getFallbackType();

        return $field;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocaleSaveValue($value)
    {
        $localeData = $this->getLocaleSaveData();
        $key = $this->valueFrom ?: $this->fieldName;

        /*
         * Set the translated values to the model
         */
        $studKey = Str::studly(implode(' ', HtmlHelper::nameToArray($key)));
        $mutateMethod = 'set'.$studKey.'AttributeTranslated';

        if ($this->objectMethodExists($this->model, $mutateMethod)) {
            foreach ($localeData as $locale => $value) {
                $this->model->$mutateMethod($value, $locale);
            }
        }
        elseif ($this->objectMethodExists($this->model, 'setAttributeTranslated')) {
            foreach ($localeData as $locale => $value) {
                $this->model->setAttributeTranslated($key, $value, $locale);
            }
        }

        return array_get($localeData, $this->defaultLocale->code, $value);
    }

    /**
     * getLocaleSaveData returns an array of translated values for this field
     * @return array
     */
    public function getLocaleSaveData()
    {
        $values = [];
        $data = post('RLTranslate');

        if (!is_array($data)) {
            return $values;
        }

        $fieldName = implode('.', HtmlHelper::nameToArray($this->fieldName));
        $isJson = $this->isLocaleFieldJsonable();

        foreach ($data as $locale => $_data) {
            $value = array_get($_data, $fieldName);
            $values[$locale] = $isJson ? json_decode($value, true) : $value;
        }

        return $values;
    }

    /**
     * getFallbackType returns the fallback field type.
     * @return string
     */
    public function getFallbackType()
    {
        return defined('static::FALLBACK_TYPE') ? static::FALLBACK_TYPE : 'text';
    }

    /**
     * isLocaleFieldJsonable returns true if widget is a repeater, or the field is specified
     * as jsonable in the model.
     * @return bool
     */
    public function isLocaleFieldJsonable()
    {
        if (
            $this instanceof \Backend\FormWidgets\Repeater ||
            $this instanceof \Backend\FormWidgets\NestedForm
        ) {
            return true;
        }

        if ($this instanceof \Media\FormWidgets\MediaFinder && $this->maxItems !== 1) {
            return true;
        }

        if (
            method_exists($this->model, 'isJsonable') &&
            $this->model->isJsonable($this->fieldName)
        ) {
            return true;
        }

        return false;
    }

    /**
     * objectMethodExists is an internal helper for method existence checks.
     *
     * @param  object $object
     * @param  string $method
     * @return boolean
     */
    protected function objectMethodExists($object, $method)
    {
        if (method_exists($object, 'methodExists')) {
            return $object->methodExists($method);
        }

        return method_exists($object, $method);
    }
}
