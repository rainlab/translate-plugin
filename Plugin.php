<?php namespace RainLab\Translate;

use App;
use Lang;
use Event;
use Backend;
use System\Classes\PluginBase;
use RainLab\Translate\Models\Message;
use RainLab\Translate\Classes\Translate;

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
            'name'        => 'Translate',
            'description' => 'No description provided yet...',
            'author'      => 'RainLab',
            'icon'        => 'icon-leaf'
        ];
    }

    public function boot()
    {
        Event::listen('cms.page.beforeDisplay', function($controller, $url, $page) {
            if (!$page) return;
            Message::setContext(Translate::instance()->getLocale(), $page->url);
        });
    }

    public function registerSettings()
    {
        return [
            'messages' => [
                'label'       => 'Messages',
                'description' => 'Translate strings used throughout the front-end.',
                'icon'        => 'icon-list-alt',
                'url'         => Backend::url('rainlab/translate/messages'),
                'order'       => 100,
                'category'    => 'Translation',
            ],
            'locales' => [
                'label'       => 'Languages',
                'description' => 'Set up languages that can be used on the front-end.',
                'icon'        => 'icon-language',
                'url'         => Backend::url('rainlab/translate/locales'),
                'order'       => 100,
                'category'    => 'Translation',
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
                '_' => [$this, 'translateString'],
                '__' => [$this, 'translatePlural'],
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

}
