<?php namespace RainLab\Translate\Models;

use Backend\Models\ImportModel;

class MessageImport extends ImportModel
{

    public $rules = [
        'code' => 'required'
    ];

    /**
     * Called when data is being imported.
     * The $results array should be in the format of:
     *
     *    [
     *        'db_name1' => 'Some value',
     *        'db_name2' => 'Another value'
     *    ],
     *    [...]
     *
     */
    public function importData($results, $sessionKey = null)
    {

    }

}