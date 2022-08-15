<?php namespace RainLab\Translate\Models;

use Backend\Models\ExportModel;
use RainLab\Translate\Classes\Locale;
use ValidationException;

class MessageExport extends ExportModel
{
    const CODE_COLUMN_NAME = 'code';
    const DEFAULT_COLUMN_NAME = 'default';

    use \October\Rain\Database\Traits\Validation;

    /**
     * @var array guarded fields
     */
    protected $guarded = [];

    /**
     * @var array fillable fields
     */
    protected $fillable = [
        'locale'
    ];

    /**
     * @var array rules to be applied to the data.
     */
    public $rules = [
        'locale' => 'required'
    ];

    /**
     * Exports the message data for a given locale.
     *
     * key    | message
     * -----------------
     * Title  | Titel
     * Name   | Prénom
     * ...
     *
     * @param $columns
     * @param null $sessionKey
     * @return mixed
     */
    public function exportData($columns, $sessionKey = null)
    {
        if (!$this->locale) {
            throw new ValidationException(['locale' => 'Please select a locale to export']);
        }

        $messages = (new Message)->findMessages($this->locale, ['withEmpty' => true]);

        $result = [];
        foreach ($messages as $key => $message){
            $result[] = compact('key', 'message');
        }

        return $result;
    }

    /**
     * getLocaleOptions returns available options for the "locale" attribute.
     * @return array
     */
    public function getLocaleOptions()
    {
        $options = [];

        foreach (Locale::listLocales() as $locale) {
            $options[$locale->code] = $locale->name;
        }

        return ['' => '-- select --'] + $options;
    }
}
