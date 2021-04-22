<?php namespace RainLab\Translate\Models;

use Cms\Models\ThemeData as ThemeDataBase;

/**
 * MLThemeData makes theme data translatable
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class MLThemeData extends ThemeDataBase
{
    /**
     * @var array implement behaviors
     */
    public $implement = [
        \RainLab\Translate\Behaviors\TranslatableModel::class
    ];

    /**
     * @var array translatable attributes
     */
    public $translatable = [];

    /**
     * afterFetch event
     */
    public function afterFetch()
    {
        parent::afterFetch();

        // Splice in translations
        foreach ($this->getFormFields() as $id => $field) {
            if (!empty($field['translatable'])) {
                $this->translatable[] = $id;
            }
        }
    }
}
