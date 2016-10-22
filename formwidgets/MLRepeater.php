<?php namespace RainLab\Translate\FormWidgets;

use Backend\FormWidgets\Repeater;
use RainLab\Translate\Models\Locale;
use October\Rain\Html\Helper as HtmlHelper;
use ApplicationException;

/**
 * ML Repeater
 * Renders a multi-lingual repeater field.
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class MLRepeater extends Repeater
{
    use \RainLab\Translate\Traits\MLControl;

    /**
     * {@inheritDoc}
     */
    protected $defaultAlias = 'mlrepeater';

    public $originalAssetPath;
    public $originalViewPath;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        parent::init();
        $this->initLocale();
    }

    /**
     * {@inheritDoc}
     */
    public function render()
    {
        $this->actAsParent();
        $parentContent = parent::render();
        $this->actAsParent(false);

        if (!$this->isAvailable) {
            return $parentContent;
        }

        $this->vars['repeater'] = $parentContent;
        return $this->makePartial('mlrepeater');

        $this->isAvailable = Locale::isAvailable();
    }

    public function prepareVars()
    {
        parent::prepareVars();
        $this->prepareLocaleVars();
    }

    /**
     * Returns an array of translated values for this field
     * @return array
     */
    public function getSaveValue($value)
    {
        $this->overridePostbackWithMappings();

        return $this->getLocaleSaveValue($value);
    }

    /**
     * {@inheritDoc}
     */
    protected function loadAssets()
    {
        $this->actAsParent();
        parent::loadAssets();
        $this->actAsParent(false);

        if (Locale::isAvailable()) {
            $this->loadLocaleAssets();
            $this->addJs('js/mlrepeater.js');
        }
    }

    protected function actAsParent($switch = true)
    {
        if ($switch) {
            $this->originalAssetPath = $this->assetPath;
            $this->originalViewPath = $this->viewPath;
            $this->assetPath = '/modules/backend/formwidgets/repeater/assets';
            $this->viewPath = base_path().'/modules/backend/formwidgets/repeater/partials';
        }
        else {
            $this->assetPath = $this->originalAssetPath;
            $this->viewPath = $this->originalViewPath;
        }
    }

    public function onAddItem()
    {
        $this->actAsParent();
        return parent::onAddItem();
    }

    public function onSwitchItemLocale()
    {
        if (
            ($locale = post('repeater_locale')) === null ||
            ($index = post('repeater_index')) === null ||
            ($widget = array_get($this->formWidgets, $index)) === null
        ) {
            throw new ApplicationException('Unable to find a repeater index at: '.$index);
        }

        $result = [];

        /*
         * Send the previous input values
         */
        $previousLocale = post('repeater_previous_locale');
        $previousData = $this->getLocaleValueAsArray($previousLocale);
        $previousData[$index] = $this->getSaveDataAtIndex($index);

        $result['updateLocale'] = $previousLocale;
        $result['updateValue'] = json_encode($previousData);

        /*
         * Update the repeater section
         */
        $section = '';

        $value = $this->getLockerDataAtIndex($locale, $index)
            ?: $this->makeStubValueForIndex($index);

        $widget->setFormValues($value);

        foreach ($widget->getFields() as $field) {
            $section .= $widget->renderField($field);
        }

        $result['formFields'] = $section;

        return $result;
    }

    protected function makeStubValueForIndex($index)
    {
        $loadValue = array_get($this->getLoadValue(), $index, []);
        return array_fill_keys(array_keys($loadValue), null);
    }

    protected function getLockerDataAtIndex($locale, $index)
    {
        return array_get($this->getLockerDataAsArray($locale), $index) ?: [];
    }

    protected function getLockerDataAsArray($locale)
    {
        $saveData = array_get($this->getLocaleSaveData(), $locale, []);

        if (!is_array($saveData)) {
            $saveData = json_decode($saveData, true);
        }

        return $saveData;
    }

    protected function getLocaleValueAsArray($locale)
    {
        $localeValue = $this->getLocaleValue($locale);

        if (!is_array($localeValue)) {
            $localeValue = json_decode($localeValue, true);
        }

        return $localeValue;
    }

    protected function getSaveDataAtIndex($index)
    {
        return array_get(post($this->formField->getName()), $index) ?: [];
    }

    //
    // Postback override
    //

    /**
     * Since the locker does always contain the latest values, this method
     * will take the save data from the repeater and merge it in to the
     * locker based on which ever locale is selected using an item map
     * @return void
     */
    protected function overridePostbackWithMappings()
    {
        $map = $this->getIndexToLocaleMap();
        $locker = [];

        foreach ($map as $locale) {
            $locker[$locale] = $this->getLockerDataAsArray($locale);
        }

        foreach ($map as $index => $locale) {
            $save = $this->getSaveDataAtIndex($index);
            $locker[$locale][$index] = $save;
        }

        foreach ($locker as $locale => $data) {
            $fieldName = 'RLTranslate.'.$locale.'.'.implode('.', HtmlHelper::nameToArray($this->fieldName));
            array_set($_POST, $fieldName, json_encode($data));
        }
    }

    /**
     * Return the item map, which repeater items are showing which locale.
     * @return array
     */
    protected function getIndexToLocaleMap()
    {
        $data = post('RLTranslateRepeaterMap');
        $fieldName = implode('.', HtmlHelper::nameToArray($this->fieldName));
        $value = array_get($data, $fieldName);
        return @json_decode($value, true);
    }

}
