<?php namespace RainLab\Translate;

use App;
use Lang;
use Event;
use System\Classes\PluginBase;
use RainLab\Translate\Models\Message;

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
            if (!$page)
                return;

            Message::setContext(App::getLocale(), $page->url);
        });
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
        traceLog('translate: '.$string);
        traceLog($params);
        return Lang::get($string, $params);
    }

    public function translatePlural($string, $count = 0, $params = [])
    {
        return Lang::choice($string, $count, $params);
    }

}
