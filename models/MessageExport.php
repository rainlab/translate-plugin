<?php namespace RainLab\Translate\Models;

use Backend\Models\ExportModel;

class MessageExport extends ExportModel
{
    const CODE_COLUMN_NAME = 'code';

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
        return Message::all()->map(function($message) use($columns) {
            $data = $message->message_data;
            // add code to data to simplify algorithm
            $data[self::CODE_COLUMN_NAME] = $message->code;

            $result = [];
            foreach ($columns as $column) {
                $result[$column] = isset($data[$column]) ? $data[$column] : '';
            }
            return  $result;
        });
    }

    public static function getColumns()
    {
        // code column + all existing locales
        return array_merge([self::CODE_COLUMN_NAME => self::CODE_COLUMN_NAME],
            Locale::lists(self::CODE_COLUMN_NAME, self::CODE_COLUMN_NAME));
    }
}