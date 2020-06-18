<?php namespace RainLab\Translate\Models;

use Backend\Models\ExportModel;

class MessageExport extends ExportModel
{
    const CODE_COLUMN_NAME = 'code';
    const DEFAULT_COLUMN_NAME = 'default';

    /**
     * Exports the message data with each locale in a separate column.
     *
     * code      | default   | en    | de    | fr
     * ----------------------------------------------
     * title     | Title     | Title | Titel | Titre
     * name      | Name      | Name  | Name  | Prénom
     * ...
     *
     * @param $columns
     * @param null $sessionKey
     * @return mixed
     */
    public function exportData($columns, $sessionKey = null)
    {
        return Message::all()->map(function ($message) use ($columns) {
            $data = $message->message_data;
            // Add code to data to simplify algorithm
            $data[self::CODE_COLUMN_NAME] = $message->code;

            $result = [];
            foreach ($columns as $column) {
                $result[$column] = isset($data[$column]) ? $data[$column] : '';
            }
            return $result;
        })->toArray();
    }

    /**
     * getColumns
     *
     * code, default column + all existing locales
     *
     * @return array
     */
    public static function getColumns()
    {
        return array_merge([
            self::CODE_COLUMN_NAME => self::CODE_COLUMN_NAME,
            Message::DEFAULT_LOCALE => self::DEFAULT_COLUMN_NAME,
        ], Locale::lists(self::CODE_COLUMN_NAME, self::CODE_COLUMN_NAME));
    }
}
