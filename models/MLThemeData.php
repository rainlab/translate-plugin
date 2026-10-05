<?php namespace RainLab\Translate\Models;

use Db;
use Cache;
use Cms\Models\ThemeData as ThemeDataBase;
use Exception;

/**
 * MLThemeData makes theme data translatable
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class MLThemeData extends ThemeDataBase
{
    /**
     * @var string STORED_DATA_CACHE_KEY remembers if data is stored under this model
     */
    const STORED_DATA_CACHE_KEY = 'rainlab.translate.themedata.stored';

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
            if (!empty($field['translatable']) && !in_array($id, $this->translatable)) {
                $this->translatable[] = $id;
            }
        }
    }

    /**
     * isTranslatableEnabled turns off the core Translatable trait, since this model translates through the plugin.
     */
    public function isTranslatableEnabled()
    {
        return false;
    }

    /**
     * setLocale switches the plugin locale, so the core translate popup edits plugin translations.
     */
    public function setLocale($locale)
    {
        $this->asExtension('TranslatableModel')->translateContext($locale);

        return $this;
    }

    /**
     * getLocale returns the plugin locale.
     */
    public function getLocale()
    {
        return $this->asExtension('TranslatableModel')->translateContext();
    }

    /**
     * hasStoredData returns true when translations or files are stored under this model, cached until the theme data import runs.
     */
    public static function hasStoredData(): bool
    {
        if (($stored = Cache::get(static::STORED_DATA_CACHE_KEY)) !== null) {
            return $stored;
        }

        try {
            $stored = Db::table('rainlab_translate_attributes')->where('model_type', static::class)->exists() ||
                Db::table('system_files')->where('attachment_type', static::class)->exists();
        }
        catch (Exception $ex) {
            return false;
        }

        Cache::forever(static::STORED_DATA_CACHE_KEY, $stored);

        return $stored;
    }

    /**
     * clearStoredDataCache forgets whether data is stored under this model.
     */
    public static function clearStoredDataCache()
    {
        Cache::forget(static::STORED_DATA_CACHE_KEY);
    }
}
