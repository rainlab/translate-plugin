<?php namespace RainLab\Translate\Models;

use Backend\Models\ExportModel;

class MessageExport extends ExportModel
{

    /**
     * Called when data is being exported.
     * The return value should be an array in the format of:
     *
     *   [
     *       'db_name1' => 'Some attribute value',
     *       'db_name2' => 'Another attribute value'
     *   ],
     *   [...]
     *
     */
    public function exportData($columns, $sessionKey = null)
    {


    }
}