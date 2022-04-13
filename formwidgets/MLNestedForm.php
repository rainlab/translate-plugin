<?php namespace RainLab\Translate\FormWidgets;

use Backend\FormWidgets\NestedForm;
use RainLab\Translate\Models\Locale;
use October\Rain\Html\Helper as HtmlHelper;
use ApplicationException;
use Request;

/**
 * MLNestedForm renders a multi-lingual nested form field.
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class MLNestedForm extends NestedForm
{
    use \RainLab\Translate\Traits\MLControl;

    /**
     * {@inheritDoc}
     */
    protected $defaultAlias = 'mlnestedform';

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

        $this->vars['nestedform'] = $parentContent;

        return $this->makePartial('mlnestedform');
    }

    /**
     * prepareVars for viewing
     */
    public function prepareVars()
    {
        parent::prepareVars();

        $this->prepareLocaleVars();
    }

    /**
     * getSaveValue returns an array of translated values for this field
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
            $this->addJs('js/mlnestedform.js');
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getParentViewPath()
    {
        return base_path().'/modules/backend/formwidgets/nestedform/partials';
    }

    /**
     * {@inheritDoc}
     */
    protected function getParentAssetPath()
    {
        return '/modules/backend/formwidgets/nestedform/assets';
    }

    /**
     * onSwitchItemLocale handler
     */
    public function onSwitchItemLocale()
    {
        if (!$locale = post('_nestedform_locale')) {
            throw new ApplicationException('Unable to find a nested form locale for: '.$locale);
        }

        // Store previous value
        $previousLocale = post('_nestedform_previous_locale');
        $previousValue = $this->getPrimarySaveDataAsArray();

        // Update widget to show form for switched locale
        $lockerData = $this->getLocaleSaveDataAsArray($locale) ?: [];
        $this->formWidget->setFormValues($lockerData);

        $this->actAsParent();
        $parentContent = parent::render();
        $this->actAsParent(false);

        return [
            '#'.$this->getId('mlNestedForm') => $parentContent,
            'updateValue' => json_encode($previousValue),
            'updateLocale' => $previousLocale,
        ];
    }

    /**
     * getPrimarySaveDataAsArray gets the active values from the selected locale.
     */
    protected function getPrimarySaveDataAsArray(): array
    {
        return post($this->formField->getName()) ?: [];
    }

    /**
     * getLocaleSaveDataAsArray returns the stored locale data as an array.
     */
    protected function getLocaleSaveDataAsArray($locale): ?array
    {
        $saveData = array_get($this->getLocaleSaveData(), $locale, []);

        if (!is_array($saveData)) {
            $saveData = json_decode($saveData, true);
        }

        return $saveData;
    }

    /**
     * rewritePostValues since the locker does always contain the latest values,
     * this method will take the save data from the nested form and merge it in to
     * the locker based on which ever locale is selected using an item map
     */
    protected function rewritePostValues()
    {
        // Get the selected locale at postback
        $data = post('RLTranslateNestedFormLocale');
        $fieldName = implode('.', HtmlHelper::nameToArray($this->fieldName));
        $locale = array_get($data, $fieldName);

        if (!$locale) {
            return;
        }

        // Splice the save data in to the locker data for selected locale
        $data = $this->getPrimarySaveDataAsArray();
        $fieldName = 'RLTranslate.'.$locale.'.'.implode('.', HtmlHelper::nameToArray($this->fieldName));

        $requestData = Request::all();
        array_set($requestData, $fieldName, json_encode($data));
        $this->mergeWithPost($requestData);
    }

    /**
     * mergeWithPost will apply postback values globally
     */
    protected function mergeWithPost(array $values)
    {
        Request::merge($values);
        $_POST = array_merge($_POST, $values);
    }
}
