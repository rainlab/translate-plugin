<?php namespace RainLab\Translate\Models;

use Backend\Models\ExportModel;

class MessageExport extends ExportModel
{
    const CODE_COLUMN_NAME = 'code';

    /**
     * exports the message data with each locale in a separate column.
     *
     * code      | x         | en    | de    | fr
     * ----------------------------------------------
     * title     | title     | Title | Titel | Titre
     * item.name | Item Name | Name  | Name  | Prénom
     * ...
     *
     * @param $columns
     * @param null $sessionKey
     * @return mixed
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
        })->toArray();
    }

    public static function getColumns()
    {
        // code, default column + all existing locales
        return array_merge([
            self::CODE_COLUMN_NAME => self::CODE_COLUMN_NAME,
            Message::DEFAULT_LOCALE => Message::DEFAULT_LOCALE,
        ], Locale::lists(self::CODE_COLUMN_NAME, self::CODE_COLUMN_NAME));
    }

}
