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
        if (!$locale = post('_repeater_locale')) {
            throw new ApplicationException('Unable to find a repeater locale for: '.$locale);
        }

        /*
         * Update widget
         */
        $lockerData = $this->getLocaleSaveDataAsArray($locale) ?: [];

        $this->formField->value = $lockerData;

        $this->reprocessExistingLocaleItems($lockerData);

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
     * Recreates form widgets based on number of repeater items.
     * @return void
     */
    protected function reprocessExistingLocaleItems($data)
    {
        $this->formWidgets = [];

        $loadedIndexes = $loadedGroups = [];

        if (is_array($data)) {
            foreach ($data as $index => $loadedValue) {
                $loadedIndexes[] = array_get($loadedValue, '_index', $index);
                $loadedGroups[] = array_get($loadedValue, '_group');
            }
        }

        $indexVar = self::INDEX_PREFIX.implode('.', HtmlHelper::nameToArray($this->formField->getName(false)));
        $groupVar = self::GROUP_PREFIX.implode('.', HtmlHelper::nameToArray($this->formField->getName(false)));

        array_set($_POST, $indexVar, $loadedIndexes);
        array_set($_POST, $groupVar, $loadedGroups);

        $this->processExistingItems();
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
        array_set($_POST, $fieldName, json_encode($data));
    }
}
