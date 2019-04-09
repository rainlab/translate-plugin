<?php namespace RainLab\Translate\FormWidgets;

use Backend\FormWidgets\Repeater;
use RainLab\Translate\Models\Locale;
use October\Rain\Html\Helper as HtmlHelper;
use ApplicationException;
use Request;

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
        $this->isAvailable = Locale::isAvailable();

        $this->actAsParent();
        $parentContent = parent::render();
        $this->actAsParent(false);

        if (!$this->isAvailable) {
            return $parentContent;
        }

        $this->vars['repeater'] = $parentContent;
        return $this->makePartial('mlrepeater');
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
        $this->rewritePostValues();

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

    /**
     * {@inheritDoc}
     */
    protected function getParentViewPath()
    {
        return base_path().'/modules/backend/formwidgets/repeater/partials';
    }

    /**
     * {@inheritDoc}
     */
    protected function getParentAssetPath()
    {
        return '/modules/backend/formwidgets/repeater/assets';
    }

    public function onAddItem()
    {
        $this->actAsParent();
        return parent::onAddItem();
    }

    /**
     * Item reorder callback
     *
     * On reorder, the MLRepeater will change the positions of the selected index to the new index value
     * for every language in the POST data, ensuring all languages reflect the same positioning of items.
     *
     * @return array
     */
    public function onReorder()
    {
        // Index positions
        $oldIndex = post('_repeater_index');
        $newIndex = post('_repeater_new_index');

        $translateData = post('RLTranslate');
        foreach ($translateData as $locale => &$data) {
            $fieldData = json_decode(array_get($data, implode('.', HtmlHelper::nameToArray($this->fieldName))));

            if (!is_array($fieldData) || !count($fieldData)) {
                continue;
            }

            // Reposition item
            $piece = array_splice($fieldData, $oldIndex, 1) ?? [];
            array_splice($fieldData, $newIndex, 0, $piece);
            array_set($data, implode('.', HtmlHelper::nameToArray($this->fieldName)), json_encode($fieldData));
        }
        unset($data);

        // Get the current field's translation data
        $fieldData = [];
        foreach ($translateData as $locale => $data) {
            $fieldData[$locale] = array_get($data, implode('.', HtmlHelper::nameToArray($this->fieldName)));
        }

        return [
            'translateData' => $fieldData
        ];
    }

    /**
     * Item removal callback
     *
     * On removing an item, remove the item from all languages in the POST data.
     *
     * @return array
     */
    public function onRemoveItem()
    {
        $index = post('_repeater_index');

        $translateData = post('RLTranslate');
        foreach ($translateData as $locale => &$data) {
            $fieldData = json_decode(array_get($data, implode('.', HtmlHelper::nameToArray($this->fieldName))));

            if (!is_array($fieldData) || !count($fieldData)) {
                continue;
            }

            // Remove item
            array_splice($fieldData, $index, 1);
            array_set($data, implode('.', HtmlHelper::nameToArray($this->fieldName)), json_encode($fieldData));
        }
        unset($data);

        // Get the current field's translation data
        $fieldData = [];
        foreach ($translateData as $locale => $data) {
            $fieldData[$locale] = array_get($data, implode('.', HtmlHelper::nameToArray($this->fieldName)));
        }

        return [
            'translateData' => $fieldData
        ];
    }

    public function onSwitchItemLocale()
    {
        if (!$locale = post('_repeater_locale')) {
            throw new ApplicationException('Unable to find a repeater locale for: '.$locale);
        }

        /*
         * Update widget
         */
        $lockerData = $this->getLocaleSaveDataAsArray($locale) ?: [];

        $this->formField->value = $lockerData;

        $this->reprocessLocaleItems($lockerData);

        foreach ($this->formWidgets as $key => $widget) {
            $value = array_shift($lockerData);
            if (!$value) {
                unset($this->formWidgets[$key]);
            }
            else {
                $widget->setFormValues($value);
            }
        }

        $this->actAsParent();
        $parentContent = parent::render();
        $this->actAsParent(false);

        /*
         * Update previous
         */
        $previousLocale = post('_repeater_previous_locale');
        $previousValue = $this->getPrimarySaveDataAsArray();

        return [
            '#'.$this->getId('mlRepeater') => $parentContent,
            'updateValue' => json_encode($previousValue),
            'updateLocale' => $previousLocale,
        ];
    }

    /**
     * Ensure that the current locale data is processed by the repeater instead of the original non-translated data
     * @return void
     */
    protected function reprocessLocaleItems($data)
    {
        $this->formWidgets = [];

        $key = implode('.', HtmlHelper::nameToArray($this->formField->getName()));
        $requestData = Request::all();
        array_set($requestData, $key, $data);
        Request::merge([$requestData]);

        $this->processItems();
    }

    /**
     * Gets the active values from the selected locale.
     * @return array
     */
    protected function getPrimarySaveDataAsArray()
    {
        $data = post($this->formField->getName()) ?: [];

        return $this->processSaveValue($data);
    }

    /**
     * Returns the stored locale data as an array.
     * @return array
     */
    protected function getLocaleSaveDataAsArray($locale)
    {
        $saveData = array_get($this->getLocaleSaveData(), $locale, []);

        if (!is_array($saveData)) {
            $saveData = json_decode($saveData, true);
        }

        return $saveData;
    }

    /**
     * Since the locker does always contain the latest values, this method
     * will take the save data from the repeater and merge it in to the
     * locker based on which ever locale is selected using an item map
     * @return void
     */
    protected function rewritePostValues()
    {
        /*
         * Get the selected locale at postback
         */
        $data = post('RLTranslateRepeaterLocale');
        $fieldName = implode('.', HtmlHelper::nameToArray($this->fieldName));
        $locale = array_get($data, $fieldName);

        if (!$locale) {
            return;
        }

        /*
         * Splice the save data in to the locker data for selected locale
         */
        $data = $this->getPrimarySaveDataAsArray();
        $fieldName = 'RLTranslate.'.$locale.'.'.implode('.', HtmlHelper::nameToArray($this->fieldName));

        $requestData = Request::all();
        array_set($requestData, $fieldName, json_encode($data));
        Request::merge($requestData);
    }
}
