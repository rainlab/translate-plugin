<?php namespace RainLab\Translate\Models;

use Model;

/**
 * Locale Model
 */
class Locale extends Model
{

    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'rainlab_translate_locales';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'code' => 'required'
    ];

    public $timestamps = false;

}