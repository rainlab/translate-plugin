<?php namespace RainLab\Translate\Models;

use Model;

/**
 * Attribute Model
 */
class Attribute extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'rainlab_translate_attributes';

    /**
     * @var bool Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    public $morphTo = [
        'model' => []
    ];
}
