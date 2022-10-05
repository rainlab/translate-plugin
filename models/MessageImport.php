<?php namespace RainLab\Translate\Models;

use Backend\Models\ImportModel;
use RainLab\Translate\Classes\Locale;
use ValidationException;
use Exception;

class MessageImport extends ImportModel
{
    /**
     * @var array rules for validation
     */
    public $rules = [];

    /**
     * Import the message data from a csv with the following schema:
     *
     * key    | message
     * -----------------
     * Title  | Titel
     * Name   | Prénom
     * ...
     *
     * JSON uses a key/pair format:
     *
     * { "Title": "Titel", "Name": "Prénom" }
     *
     * The code column is required and must not be empty.
     *
     * Note: Messages with an existing code are not removed/touched if the import
     * doesn't contain this code. As a result you can incrementally update the
     * messages by just adding the new codes and messages to the file.
     *
     * @param $results
     * @param null $sessionKey
     */
    public function importData($results, $sessionKey = null)
    {
        if (!$this->locale) {
            throw new ValidationException(['locale' => 'Please select a locale to export']);
        }

        $knownMessages = Message::getMessages($this->locale, ['withEmpty' => false]);
        $messages = [];
        $count = 0;
        foreach ($results as $key => $result) {
            $count++;
            try {
                $exists = false;
                if ($this->file_format === 'json') {
                    $messages[$key] = $result;
                    $exists = isset($knownMessages[$key]);
                }
                else {
                    $_key = $result['key'];
                    $messages[$_key] = $result['message'];
                    $exists = isset($knownMessages[$_key]);
                }

                if ($exists) {
                    $this->logUpdated();
                }
                else {
                    $this->logCreated();
                }
            }
            catch (Exception $exception) {
                $this->logError($count, $exception->getMessage());
            }
        }

        (new Message)->updateMessages($this->locale, $messages);
    }

    /**
     * getLocaleOptions returns available options for the "locale" attribute.
     * @return array
     */
    public function getLocaleOptions()
    {
        $options = [];

        foreach (Locale::listLocales() as $locale) {
            $options[$locale->code] = "{$locale->name} [$locale->code]";
        }

        // Make the active locale first and therefore default
        $locale = Locale::getSiteLocaleFromContext();
        if ($active = array_pull($options, $locale)) {
            $options = [$locale => $active] + $options;
        }

        return $options;
    }
}
