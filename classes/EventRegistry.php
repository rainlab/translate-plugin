<?php namespace RainLab\Translate\Classes;

use App;
use Exception;
use File;
use Mail;
use Request;
use Str;
use Cms\Classes\Page;
use Cms\Classes\Content;
use System\Classes\MailManager;
use System\Classes\PluginManager;
use RainLab\Translate\Models\Message;
use RainLab\Translate\Models\Locale as LocaleModel;
use RainLab\Translate\Classes\Translator;
use RainLab\Translate\Classes\ThemeScanner;

/**
 * Registrant class for bootstrapping events
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class EventRegistry
{
    use \October\Rain\Support\Traits\Singleton;

    //
    // Utility
    //

    public function registerFormFieldReplacements($widget)
    {
        // Replace with ML Controls for translatable attributes
        $this->registerModelTranslation($widget);

        // Handle URL translations
        $this->registerPageUrlTranslation($widget);

        // Handle RainLab.Pages MenuItem translations
        if (PluginManager::instance()->exists('RainLab.Pages')) {
            $this->registerMenuItemTranslation($widget);
        }
    }

    /**
     * Translate RainLab.Pages MenuItem data
     *
     * @param Backend\Widgets\Form $widget
     * @return void
     */
    public function registerMenuItemTranslation($widget)
    {
        if ($widget->model instanceof \RainLab\Pages\Classes\MenuItem) {
            $defaultLocale = LocaleModel::getDefault();
            $availableLocales = LocaleModel::listAvailable();
            $fieldsToTranslate = ['title', 'url'];
            
            // Replace specified fields with multilingual versions
            foreach ($fieldsToTranslate as $fieldName) {
                $widget->fields[$fieldName]['type'] = 'mltext';
                
                foreach ($availableLocales as $code => $locale) {
                    if (!$defaultLocale || $defaultLocale->code === $code) {
                        continue;
                    }
                    
                    // Add data locker fields for the different locales under the `viewBag[locale]` property
                    $widget->fields["viewBag[locale][$code][$fieldName]"] = [
                        'cssClass' => 'hidden',
                        'attributes' => [
                            'data-locale' => $code,
                            'data-field-name' => $fieldName,
                        ],
                    ];
                }
            }
        }
    }

    //
    // Translate URLs
    //

    public function registerPageUrlTranslation($widget)
    {
        if (!$model = $widget->model) {
            return;
        }

        if (
            $model instanceof Page &&
            isset($widget->fields['settings[url]'])
        ) {
            $widget->fields['settings[url]']['type'] = 'mltext';
        }
        elseif (
            $model instanceof \RainLab\Pages\Classes\Page &&
            isset($widget->fields['viewBag[url]'])
        ) {
            $widget->fields['viewBag[url]']['type'] = 'mltext';
        }
    }

    //
    // Translatable behavior
    //

    /**
     * Automatically replace form fields for multi lingual equivalents
     */
    public function registerModelTranslation($widget)
    {
        if (!$model = $widget->model) {
            return;
        }

        if (!method_exists($model, 'isClassExtendedWith')) {
            return;
        }

        if (
            !$model->isClassExtendedWith('RainLab.Translate.Behaviors.TranslatableModel') &&
            !$model->isClassExtendedWith('RainLab.Translate.Behaviors.TranslatablePage') &&
            !$model->isClassExtendedWith('RainLab.Translate.Behaviors.TranslatableCmsObject')
        ) {
            return;
        }


        if (!$model->hasTranslatableAttributes()) {
            return;
        }

        if (!empty($widget->config->fields) && !$widget->isNested) {
            $widget->fields = $this->processFormMLFields($widget->fields, $model);
        }

        if (!empty($widget->config->tabs['fields'])) {
            $widget->tabs['fields'] = $this->processFormMLFields($widget->tabs['fields'], $model);
        }

        if (!empty($widget->config->secondaryTabs['fields'])) {
            $widget->secondaryTabs['fields'] = $this->processFormMLFields($widget->secondaryTabs['fields'], $model);
        }
    }

    /**
     * Helper function to replace standard fields with multi lingual equivalents
     * @param  array $fields
     * @param  Model $model
     * @return array
     */
    protected function processFormMLFields($fields, $model)
    {
        $translatable = array_flip($model->getTranslatableAttributes());

        /*
         * Special: A custom field "markup_html" is used for Content templates.
         */
        if ($model instanceof Content && array_key_exists('markup', $translatable)) {
            $translatable['markup_html'] = true;
        }

        foreach ($fields as $name => $config) {
            if (!array_key_exists($name, $translatable)) {
                continue;
            }

            $type = array_get($config, 'type', 'text');

            if ($type == 'text') {
                $fields[$name]['type'] = 'mltext';
            }
            elseif ($type == 'textarea') {
                $fields[$name]['type'] = 'mltextarea';
            }
            elseif ($type == 'richeditor') {
                $fields[$name]['type'] = 'mlricheditor';
            }
            elseif ($type == 'markdown') {
                $fields[$name]['type'] = 'mlmarkdowneditor';
            }
            elseif ($type == 'repeater') {
                $fields[$name]['type'] = 'mlrepeater';
            }
            elseif ($type == 'mediafinder') {
                $fields[$name]['type'] = 'mlmediafinder';
            }
        }

        return $fields;
    }

    //
    // Theme
    //

    /**
     * Import messages defined by the theme
     */
    public function importMessagesFromTheme()
    {
        try {
            (new ThemeScanner)->scanThemeConfigForMessages();
        }
        catch (Exception $ex) {}
    }

    //
    // CMS objects
    //

    /**
     * Set the page context for translation caching.
     */
    public function setMessageContext($page)
    {
        if (!$page) {
            return;
        }

        $translator = Translator::instance();

        Message::setContext($translator->getLocale(), $page->url);
    }

    /**
     * Adds language suffixes to content files.
     * @return string|null
     */
    public function findTranslatedContentFile($controller, $fileName)
    {
        if (!strlen(File::extension($fileName))) {
            $fileName .= '.htm';
        }

        /*
         * Splice the active locale in to the filename
         * - content.htm -> content.en.htm
         */
        $locale = Translator::instance()->getLocale();
        $fileName = substr_replace($fileName, '.'.$locale, strrpos($fileName, '.'), 0);
        if (($content = Content::loadCached($controller->getTheme(), $fileName)) !== null) {
            return $content;
        }
    }

    //
    // Static pages
    //

    /**
     * Removes localized content files from templates collection
     * @param \October\Rain\Database\Collection $templates
     * @return \October\Rain\Database\Collection
     */
    public function pruneTranslatedContentTemplates($templates)
    {
        $locales = LocaleModel::listAvailable();

        $extensions = array_map(function($ext) {
            return '.'.$ext;
        }, array_keys($locales));

        return $templates->filter(function($template) use ($extensions) {
            return !Str::endsWith($template->getBaseFileName(), $extensions);
        });
    }

    public function addLocalizedContent($mailer, $message, $view, $data, $raw, $plain)
    {
        if ($raw !== null || $view === null || !is_string($view)) {
            return;
        }

        if (App::runningInBackend()) {
            list($defaultLocale) = explode('-', Request::getDefaultLocale());
            list($locale) = explode('-', App::getLocale());
        } else {
            $translator = Translator::instance();
            $locale = $translator->getLocale();
            $defaultLocale = $translator->getDefaultLocale();
        }

        if ($locale === $defaultLocale) {
            return;
        }

        $view = sprintf('%s-%s', $view, $locale);

        $factory = Mail::getViewFactory();
        if (!$factory->exists($view)) {
            return;
        }

        $mailManager = MailManager::instance();
        return !$mailManager->addContentToMailer($message, $view, $data, false);
    }
}
