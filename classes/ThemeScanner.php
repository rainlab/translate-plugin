<?php namespace RainLab\Translate\Classes;

use Cms\Classes\Page;
use Cms\Classes\Theme;
use Cms\Classes\Layout;
use Cms\Classes\Partial;
use Cms\Classes\ComponentManager;
use Cms\Classes\ComponentPartial;
use RainLab\Translate\Models\Message;
use System\Models\MailTemplate;
use Exception;
use Event;

/**
 * ThemeScanner class
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class ThemeScanner
{
    /**
     * @var array|null foundMessages keys by the scanner
     */
    protected $foundMessages;

    /**
     * scan is a helper method for scanForMessages()
     */
    public static function scan()
    {
        $obj = new static;

        $obj->scanForMessages();

        /**
         * @event rainlab.translate.themeScanner.afterScan
         * Fires after theme scanning.
         *
         * Example usage:
         *
         *     Event::listen('rainlab.translate.themeScanner.afterScan', function (ThemeScanner $scanner) {
         *         // added an extra scan. Add generation files...
         *     });
         *
         */
        Event::fire('rainlab.translate.themeScanner.afterScan', [$obj]);

        return $obj;
    }

    /**
     * scanForMessages in theme templates and config.
     */
    public function scanForMessages()
    {
        $this->foundMessages = [];
        $this->scanThemeConfigForMessages();
        $this->scanThemeTemplatesForMessages();
        $this->scanCmsComponentsForMessages();
        $this->scanMailTemplatesForMessages();
    }

    /**
     * importMessages will import scanned messages, use withKeys if the messages
     * also contain their translation key, e.g [my_code => My Code]
     */
    public function importMessages($messages, $locale = null, $withKeys = false)
    {
        if (!$withKeys) {
            $messages = array_combine($messages, $messages);
        }

        Message::importMessageCodes($messages, $locale);

        $this->foundMessages += $messages;
    }

    /**
     * getFoundMessages
     */
    public function getFoundMessages()
    {
        return $this->foundMessages;
    }

    /**
     * purgeMissingMessages is used after a scan to purge missing messages from the current array
     */
    public function purgeMissingMessages($currentMessages)
    {
        if (!is_array($this->foundMessages)) {
            throw new Exception('Please run a scan first to avoid purging all messages.');
        }

        $missingMessages = array_diff_key($currentMessages, $this->foundMessages);

        (new Message)->deleteMessages(array_keys($missingMessages));
    }

    /**
     * Scans the theme configuration for defined messages
     * @return void
     */
    public function scanThemeConfigForMessages($themeCode = null)
    {
        if (!$themeCode) {
            $theme = Theme::getActiveTheme();

            if (!$theme) {
                return;
            }
        }
        else {
            if (!Theme::exists($themeCode)) {
                return;
            }

            $theme = Theme::load($themeCode);
        }

        // Parent theme support
        if ($theme->hasParentTheme()) {
            $parentTheme = $theme->getParentTheme();

            try {
                if (!$this->scanThemeConfigForMessagesInternal($theme)) {
                    $this->scanThemeConfigForMessagesInternal($parentTheme);
                }
            }
            catch (Exception $ex) {
                $this->scanThemeConfigForMessagesInternal($parentTheme);
            }
        }
        else {
            $this->scanThemeConfigForMessagesInternal($theme);
        }
    }

    /**
     * scanThemeConfigForMessagesInternal
     */
    protected function scanThemeConfigForMessagesInternal(Theme $theme)
    {
        $config = $theme->getConfigArray('translate');

        if (!count($config)) {
            return false;
        }

        foreach ($config as $locale => $messages) {
            // Config references an external yaml file
            if (is_string($messages)) {
                $messages = $theme->getConfigArray('translate.'.$locale);
            }

            if (is_array($messages)) {
                $this->importMessages($messages, $locale, true);
            }
        }
    }

    /**
     * scanThemeTemplatesForMessages
     */
    public function scanThemeTemplatesForMessages()
    {
        $messages = [];

        foreach (Layout::all() as $layout) {
            $messages = array_merge($messages, $this->parseContent($layout->markup));
        }

        foreach (Page::all() as $page) {
            $messages = array_merge($messages, $this->parseContent($page->markup));
        }

        foreach (Partial::all() as $partial) {
            $messages = array_merge($messages, $this->parseContent($partial->markup));
        }

        $this->importMessages($messages);
    }

    /**
     * scanCmsComponentsForMessages
     */
    public function scanCmsComponentsForMessages()
    {
        $messages = [];

        $manager = ComponentManager::instance();

        foreach ($manager->listComponents() as $componentClass) {
            $componentObj = $manager->makeComponent($componentClass);

            $partial = ComponentPartial::load($componentObj, 'default');
            if ($partial) {
                $messages = array_merge($messages, $this->parseContent($partial->content));
            }
        }

        $this->importMessages($messages);
    }

    /**
     * scanMailTemplatesForMessages
     */
    public function scanMailTemplatesForMessages()
    {
        $messages = [];

        foreach (MailTemplate::allTemplates() as $mailTemplate) {
            $messages = array_merge($messages, $this->parseContent($mailTemplate->subject));
            $messages = array_merge($messages, $this->parseContent($mailTemplate->content_html));
        }

        $this->importMessages($messages);
    }

    /**
     * Parse the known language tag types in to messages.
     * @param  string $content
     * @return array
     */
    public function parseContent($content)
    {
        $messages = [];
        $messages = array_merge($messages, $this->processStandardTags($content));

        return $messages;
    }

    /**
     * Process standard language filter tag (_|)
     * @param  string $content
     * @return array
     */
    protected function processStandardTags($content)
    {
        $messages = [];

        /*
         * Regex used:
         *
         * {{'AJAX framework'|_}}
         * {{\s*'([^'])+'\s*[|]\s*_\s*}}
         *
         * {{'AJAX framework'|_(variables)}}
         * {{\s*'([^'])+'\s*[|]\s*_\s*\([^\)]+\)\s*}}
         */

        $quoteChar = preg_quote("'");

        preg_match_all('#{{\s*'.$quoteChar.'([^'.$quoteChar.']+)'.$quoteChar.'\s*[|]\s*_\s*(?:[|].+)?}}#', $content, $match);
        if (isset($match[1])) {
            $messages = array_merge($messages, $match[1]);
        }

        preg_match_all('#{{\s*'.$quoteChar.'([^'.$quoteChar.']+)'.$quoteChar.'\s*[|]\s*_\s*\([^\)]+\)\s*}}#', $content, $match);
        if (isset($match[1])) {
            $messages = array_merge($messages, $match[1]);
        }

        $quoteChar = preg_quote('"');

        preg_match_all('#{{\s*'.$quoteChar.'([^'.$quoteChar.']+)'.$quoteChar.'\s*[|]\s*_\s*(?:[|].+)?}}#', $content, $match);
        if (isset($match[1])) {
            $messages = array_merge($messages, $match[1]);
        }

        preg_match_all('#{{\s*'.$quoteChar.'([^'.$quoteChar.']+)'.$quoteChar.'\s*[|]\s*_\s*\([^\)]+\)\s*}}#', $content, $match);
        if (isset($match[1])) {
            $messages = array_merge($messages, $match[1]);
        }

        return $messages;
    }
}
