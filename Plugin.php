<?php namespace RainLab\Translate;

use App;
use Str;
use Lang;
use File;
use Event;
use Backend;
use Cms\Classes\Page;
use Cms\Classes\Content;
use System\Classes\PluginBase;
use RainLab\Translate\Models\Message;
use RainLab\Translate\Models\Locale as LocaleModel;
use RainLab\Translate\Classes\Translator;
use RainLab\Translate\Classes\ThemeScanner;
use Exception;

/**
 * Translate Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'rainlab.translate::lang.plugin.name',
            'description' => 'rainlab.translate::lang.plugin.description',
            'author'      => 'Alexey Bobkov, Samuel Georges',
            'icon'        => 'icon-language',
            'homepage'    => 'https://github.com/rainlab/translate-plugin'
        ];
    }

    public function register()
    {
        /*
         * Defer event 2 levels deep to let others contribute before this registers.
         */
        Event::listen('backend.form.extendFieldsBefore', function($widget) {
            $widget->bindEvent('form.extendFieldsBefore', function() use ($widget) {
                $this->registerModelTranslation($widget);
            });
        });
    }

    public function boot()
    {
        /*
         * Set the page context for translation caching.
         */
        Event::listen('cms.page.beforeDisplay', function($controller, $url, $page) {
            if (!$page) {
                return;
            }
            $translator = Translator::instance();
            Message::setContext($translator->getLocale(), $page->url);
        });

        /*
         * Adds language suffixes to content files.
         */
        Event::listen('cms.page.beforeRenderContent', function($controller, $fileName) {
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
        });

        /*
         * Import messages defined by the theme
         */
        Event::listen('cms.theme.setActiveTheme', function($code) {
            try {
                (new ThemeScanner)->scanThemeConfigForMessages();
            }
            catch (Exception $ex) {}
        });

        /*
         * Prune localized content files from template list
         */
        Event::listen('pages.content.templateList', function($widget, $templates) {
            return $this->pruneTranslatedContentTemplates($templates);
        });
    }

    public function registerComponents()
    {
        return [
           'RainLab\Translate\Components\LocalePicker' => 'localePicker'
        ];
    }

    public function registerPermissions()
    {
        return [
            'rainlab.translate.manage_locales'  => [
                'tab'   => 'rainlab.translate::lang.plugin.tab',
                'label' => 'rainlab.translate::lang.plugin.manage_locales'
            ],
            'rainlab.translate.manage_messages' => [
                'tab'   => 'rainlab.translate::lang.plugin.tab',
                'label' => 'rainlab.translate::lang.plugin.manage_messages'
            ]
        ];
    }

    public function registerSettings()
    {
        return [
            'locales' => [
                'label'       => 'rainlab.translate::lang.locale.title',
                'description' => 'rainlab.translate::lang.plugin.description',
                'icon'        => 'icon-language',
                'url'         => Backend::url('rainlab/translate/locales'),
                'order'       => 550,
                'category'    => 'rainlab.translate::lang.plugin.name',
                'permissions' => ['rainlab.translate.manage_locales']
            ],
            'messages' => [
                'label'       => 'rainlab.translate::lang.messages.title',
                'description' => 'rainlab.translate::lang.messages.description',
                'icon'        => 'icon-list-alt',
                'url'         => Backend::url('rainlab/translate/messages'),
                'order'       => 551,
                'category'    => 'rainlab.translate::lang.plugin.name',
                'permissions' => ['rainlab.translate.manage_messages']
            ]
        ];
    }

    /**
     * Register new Twig variables
     * @return array
     */
    public function registerMarkupTags()
    {
        return [
            'filters' => [
                '_'  => [$this, 'translateString'],
                '__' => [$this, 'translatePlural']
            ]
        ];
    }

    public function registerFormWidgets()
    {
        return [
            'RainLab\Translate\FormWidgets\MLText' => [
                'label' => 'Text (ML)',
                'code'  => 'mltext'
            ],
            'RainLab\Translate\FormWidgets\MLTextarea' => [
                'label' => 'Textarea (ML)',
                'code'  => 'mltextarea'
            ],
            'RainLab\Translate\FormWidgets\MLRichEditor' => [
                'label' => 'Rich Editor (ML)',
                'code'  => 'mlricheditor'
            ],
            'RainLab\Translate\FormWidgets\MLMarkdownEditor' => [
                'label' => 'Markdown Editor (ML)',
                'code'  => 'mlmarkdowneditor'
            ]
        ];
    }

    public function translateString($string, $params = [])
    {
        return Message::trans($string, $params);
    }

    public function translatePlural($string, $count = 0, $params = [])
    {
        return Lang::choice($string, $count, $params);
    }

    /**
     * Automatically replace form fields for multi lingual equivalents
     */
    protected function registerModelTranslation($widget)
    {
        if (!$model = $widget->model) {
            return;
        }

        if (!method_exists($model, 'isClassExtendedWith')) {
            return;
        }

        if (
            !$model->isClassExtendedWith('RainLab.Translate.Behaviors.TranslatableModel') &&
            !$model->isClassExtendedWith('RainLab.Translate.Behaviors.TranslatableCmsObject')
        ) {
            return;
        }

        if (!is_array($model->translatable)) {
            return;
        }

        if (!empty($widget->config->fields)) {
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
        $translatable = array_flip($model->translatable);

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
        }

        return $fields;
    }

    /**
     * Removes localized content files from templates collection
     * @param \October\Rain\Database\Collection $templates
     * @return \October\Rain\Database\Collection
     */
    protected function pruneTranslatedContentTemplates($templates)
    {
        $locales = LocaleModel::listAvailable();

        $extensions = array_map(function($ext) {
            return '.'.$ext;
        }, array_keys($locales));

        return $templates->filter(function($template) use ($extensions) {
            return !Str::endsWith($template->getBaseFileName(), $extensions);
        });
    }
}
