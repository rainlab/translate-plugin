<?php namespace Rainlab\Translate\Console;

use Db;
use Schema;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use RainLab\Translate\Classes\ThemeScanner;
use RainLab\Translate\Models\Message;

/**
 * MigrateV1Command
 */
class MigrateV1Command extends Command
{
    /**
     * @var string name
     */
    protected $name = 'translate:migratev1';

    /**
     * @var string description
     */
    protected $description = 'Migrates theme translations to newer format without any data destruction';

    /**
     * @var array langData for each locale
     */
    protected $langData = [];

    /**
     * handle
     */
    public function handle()
    {
        if (!Schema::hasTable('rainlab_translate_messages')) {
            $this->info("Table [rainlab_translate_messages] is not found, nothing to migrate.");
            return;
        }

        $langOut = [];
        $messages = Db::table('rainlab_translate_messages')->pluck('message_data')->all();

        foreach ($messages as $message) {
            $langOut = $this->processMessage($message, $langOut);
        }

        foreach ($this->langData as $locale => $data) {
            $model = Message::firstOrNew(['locale' => $locale]);
            $existingData = (array) $model->data;
            $model->locale = $locale;
            $model->data = array_merge($existingData, $data);
            $model->save();

            $this->info("Successfully migrated data for [$locale] locale");
        }
    }

    /**
     * processMessage
     */
    protected function processMessage($json)
    {
        $data = json_decode($json, true);
        if (!isset($data['x'])) {
            $this->info("There is no usable key for [$json]");
            return;
        }

        $key = $data['x'];
        foreach ($data as $locale => $message) {
            if ($locale === 'x') {
                continue;
            }

            $this->seeMessage($locale, $key, $message);
        }
    }

    /**
     * seeMessage
     */
    protected function seeMessage($locale, $key, $message)
    {
        $this->langData[$locale][$key] = $message;
    }

    /**
     * getArguments
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * getOptions
     */
    protected function getOptions()
    {
        return [];
    }
}
