<?php namespace RainLab\Translate;

use App;
use Lang;
use Event;
use System;
use Backend;
use System\Classes\PluginBase;
use System\Classes\CombineAssets;
use System\Classes\SettingsManager;
use RainLab\Translate\Models\Message;
use RainLab\Translate\Classes\EventCoreRegistry;
use RainLab\Translate\Classes\EventPluginRegistry;
use RainLab\Translate\Classes\Translator;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * pluginDetails returns information about this plugin.
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Translate',
            'description' => 'Enables multi-lingual websites.',
            'author' => 'Alexey Bobkov, Samuel Georges',
            'icon' => 'icon-language',
            'homepage' => 'https://github.com/rainlab/translate-plugin'
        ];
    }

    /**
     * register the plugin
     */
    public function register()
    {
        EventCoreRegistry::instance()->registerEvents();
        EventPluginRegistry::instance()->registerEvents();

        // Register console commands
        $this->registerConsoleCommand('translate.scan', \Rainlab\Translate\Console\ScanCommand::class);
        $this->registerConsoleCommand('translate.migratev1', \Rainlab\Translate\Console\MigrateV1Command::class);

        // Register asset bundles
        $this->registerAssetBundles();
    }

    /**
     * boot the plugin
     */
    public function boot()
    {
        EventCoreRegistry::instance()->bootEvents();
        EventPluginRegistry::instance()->bootEvents();

        if (System::checkDebugMode()) {
            App::after(function() {
                Message::saveObserver();
            });
        }

        // Append current locale to static page's cache keys
        $modifyKey = function (&$key) {
            $key = $key . '-' . Lang::getLocale();
        };
        Event::listen('pages.router.getCacheKey', $modifyKey);
        Event::listen('pages.page.getMenuCacheKey', $modifyKey);
        Event::listen('pages.snippet.getMapCacheKey', $modifyKey);
        Event::listen('pages.snippet.getPartialMapCacheKey', $modifyKey);

        if (class_exists('\RainLab\Pages\Classes\SnippetManager')) {
            $handler = function ($controller, $template, $type) {
                if (!$template->methodExists('getDirtyLocales')) {
                    return;
                }

                // Get the locales that have changed
                $dirtyLocales = $template->getDirtyLocales();

                if (!empty($dirtyLocales)) {
                    $currentLocale = Lang::getLocale();

                    foreach ($dirtyLocales as $locale) {
                        if (!$template->isTranslateDirty(null, $locale)) {
                            continue;
                        }

                        // Clear the RainLab.Pages caches for each dirty locale
                        App::setLocale($locale);
                        \RainLab\Pages\Plugin::clearCache();
                    }

                    // Restore the original locale for this request
                    App::setLocale($currentLocale);
                }
            };

            Event::listen('cms.template.save', $handler);
            Event::listen('pages.object.save', $handler);
        }
    }

    /**
     * registerPermissions
     */
    public function registerPermissions()
    {
        return [
            'rainlab.translate.manage_messages' => [
                'tab' => 'Translation',
                'label' => 'Manage messages'
            ]
        ];
    }

    /**
     * registerSettings
     */
    public function registerSettings()
    {
        return [
            'messages' => [
                'label' => 'Translate Messages',
                'description' => 'Update messages used by the theme',
                'icon' => 'icon-list-alt',
                'url' => Backend::url('rainlab/translate/messages'),
                'order' => 551,
                'category' => SettingsManager::CATEGORY_CMS,
                'permissions' => ['rainlab.translate.manage_messages'],
                'keywords' => 'translate',
            ]
        ];
    }

    /**
     * registerMarkupTags for Twig
     * @return array
     */
    public function registerMarkupTags()
    {
        return [
            'filters' => [
                '_'  => [$this, 'translateString'],
                '__' => [$this, 'translatePlural'],
                'transRaw'  => [$this, 'translateRawString'],
                'transRawPlural' => [$this, 'translateRawPlural'],
                'localeUrl' => [$this, 'localeUrl'],
                'localePage' => [$this, 'localePage'],
            ]
        ];
    }

    /**
     * registerAssetBundles for compilation
     */
    protected function registerAssetBundles()
    {
        CombineAssets::registerCallback(function ($combiner) {
            $combiner->registerBundle('$/rainlab/translate/assets/less/messages.less');
        });
    }

    /**
     * localeUrl builds a localized URL
     */
    public function localeUrl($url, $locale)
    {
        $translator = Translator::instance();

        $parts = parse_url($url);

        $path = array_get($parts, 'path');

        return http_build_url($parts, [
            'path' => '/' . $translator->getPathInLocale($path, $locale)
        ]);
    }

    /**
     * localePage builds a page URL
     */
    public function localePage($name, $locale, $params = [])
    {
        return Translator::instance()->getPageInLocale($name, $locale, $params);
    }

    /**
     * translateString
     */
    public function translateString($string, $params = [], $locale = null)
    {
        return Message::trans($string, $params, $locale);
    }

    /**
     * translatePlural
     */
    public function translatePlural($string, $count = 0, $params = [], $locale = null)
    {
        return Lang::choice(Message::trans($string, $params, $locale), $count, $params);
    }

    /**
     * translateRawString
     */
    public function translateRawString($string, $params = [], $locale = null)
    {
        return Message::transRaw($string, $params, $locale);
    }

    /**
     * translateRawPlural
     */
    public function translateRawPlural($string, $count = 0, $params = [], $locale = null)
    {
        return Lang::choice(Message::transRaw($string, $params, $locale), $count, $params);
    }
}
