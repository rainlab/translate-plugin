<?php namespace RainLab\Translate\Models;

use Model;

/**
 * Language Plugin Preferences
 *
 * @package rainlab\translate
 * @author Justin Lau
 */
class Preferences extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'rainlab_translate_preferences';

    public $settingsFields = 'fields.yaml';

    /**
     * Default values to set for this model
     */
    public function initSettingsData()
    {
        $this->always_prefix_language_code = false;
    }
}
