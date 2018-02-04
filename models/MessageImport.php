<?php namespace RainLab\Translate\Models;

use Backend\Models\ImportModel;

class MessageImport extends ImportModel
{

    public $rules = [
        'code' => 'required'
    ];

    /**
     * import the message data from a csv.
     * the code column is required and must not be empty.
     *
     * @param $results
     * @param null $sessionKey
     */
    public function importData($results, $sessionKey = null)
    {
        $codeName = MessageExport::CODE_COLUMN_NAME;

        foreach ($results as $index => $result) {
            try {
                if (isset($result[$codeName]) && !empty($result[$codeName])) {
                    $code = $result[$codeName];
                    // modify result to match the expected message_data schema
                    unset($result[$codeName]);
                    $result[Message::DEFAULT_LOCALE] = $code;

                    $message = Message::firstOrNew(['code' => $code]);
                    $message->message_data = array_merge($message->message_data, $result);

                    if ($message->exists) {
                        $this->logUpdated();
                    } else {
                        $this->logCreated();
                    }
                    $message->save();
                } else {
                    $this->logSkipped($index, 'no code provided');
                }
            } catch (\Exception $exception) {
                $this->logError($index, $exception->getMessage());
            }
        }
    }

}