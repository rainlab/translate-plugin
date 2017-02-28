<?php namespace RainLab\Translate;

use Lang;
use Event;
use Backend;
use Cms\Classes\Page;
use System\Classes\PluginBase;
use RainLab\Translate\Models\Message;
use RainLab\Translate\Classes\EventRegistry;
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
         * Defer event with low priority to let others contribute before this registers.
         */
        Event::listen('backend.form.extendFieldsBefore', function($widget) {
            EventRegistry::instance()->registerFormFieldReplacements($widget);
        }, -1);

        /*
         * Handle translated page URLs
         */
        Page::extend(function($page) {
            $page->extendClassWith('RainLab\Translate\Behaviors\TranslatablePageUrl');
        });
    }

    public function boot()
    {
        /*
         * Set the page context for translation caching with high priority.
         */
        Event::listen('cms.page.init', function($controller, $page) {
            EventRegistry::instance()->setMessageContext($page);
        }, 100);

        /*
         * Import messages defined by the theme
         */
        Event::listen('cms.theme.setActiveTheme', function($code) {
            EventRegistry::instance()->importMessagesFromTheme();
        });

        /*
         * Adds language suffixes to content files.
         */
        Event::listen('cms.page.beforeRenderContent', function($controller, $fileName) {
            return EventRegistry::instance()
                ->findTranslatedContentFile($controller, $fileName)
            ;
        });

        /*
         * Prune localized content files from template list
         */
        Event::listen('pages.content.templateList', function($widget, $templates) {
            return EventRegistry::instance()
                ->pruneTranslatedContentTemplates($templates)
            ;
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
            'RainLab\Translate\FormWidgets\MLText' => 'mltext',
            'RainLab\Translate\FormWidgets\MLTextarea' => 'mltextarea',
            'RainLab\Translate\FormWidgets\MLRichEditor' => 'mlricheditor',
            'RainLab\Translate\FormWidgets\MLMarkdownEditor' => 'mlmarkdowneditor',
            'RainLab\Translate\FormWidgets\MLRepeater' => 'mlrepeater',
        ];
    }

    public function translateString($string, $params = [])
    {
        return Message::trans($string, $params);
    }

    public function translatePlural($string, $count = 0, $params = [])
    {
        return Lang::choice(Message::trans($string, $params), $count, $params);
    }
}
