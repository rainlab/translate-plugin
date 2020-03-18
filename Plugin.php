<?php namespace RainLab\Translate;

use App;
use Lang;
use Event;
use Backend;
use Cms\Classes\Page;
use Cms\Classes\Theme;
use System\Models\File;
use Cms\Models\ThemeData;
use System\Classes\PluginBase;
use System\Classes\CombineAssets;
use RainLab\Translate\Models\Message;
use RainLab\Translate\Classes\EventRegistry;
use RainLab\Translate\Classes\Translator;

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
            if (!$page->propertyExists('translatable')) {
                $page->addDynamicProperty('translatable', []);
            }
            $page->translatable = array_merge($page->translatable, ['title', 'description', 'meta_title', 'meta_description']);
            $page->extendClassWith('RainLab\Translate\Behaviors\TranslatablePageUrl');
            $page->extendClassWith('RainLab\Translate\Behaviors\TranslatablePage');
        });

        /*
         * Add translation support to file models
         */
        File::extend(function ($model) {
            if (!$model->propertyExists('translatable')) {
                $model->addDynamicProperty('translatable', []);
            }
            $model->translatable = array_merge($model->translatable, ['title', 'description']);
            $model->extendClassWith('October\Rain\Database\Behaviors\Purgeable');
            $model->extendClassWith('RainLab\Translate\Behaviors\TranslatableModel');
        });

        /*
         * Add translation support to theme settings
         */
        ThemeData::extend(static function ($model) {
            if (!$model->propertyExists('translatable')) {
                $model->addDynamicProperty('translatable', []);
            }

            $model->extendClassWith('October\Rain\Database\Behaviors\Purgeable');
            $model->extendClassWith('RainLab\Translate\Behaviors\TranslatableModel');

            $model->bindEvent('model.afterFetch', static function() use ($model) {
                foreach ($model->getFormFields() as $id => $field) {
                    if (!empty($field['translatable'])) {
                        $model->translatable[] = $id;
                    }
                }
            });
        });

        /*
         * Register console commands
         */
        $this->registerConsoleCommand('translate.scan', 'Rainlab\Translate\Console\ScanCommand');

        $this->registerAssetBundles();
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

        /*
         * Look at session for locale using middleware
         */
        \Cms\Classes\CmsController::extend(function($controller) {
            $controller->middleware(\RainLab\Translate\Classes\LocaleMiddleware::class);
        });

        /**
         * Append current locale to static page's cache keys
         */
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
                    $theme = Theme::getEditTheme();
                    $currentLocale = Lang::getLocale();

                    foreach ($dirtyLocales as $locale) {
                        if (!$template->isTranslateDirty(null, $locale)) {
                            continue;
                        }

                        // Clear the RainLab.Pages caches for each dirty locale
                        App::setLocale($locale);
                        \RainLab\Pages\Classes\Page::clearMenuCache($theme);
                        \RainLab\Pages\Classes\SnippetManager::clearCache($theme);
                    }

                    // Restore the original locale for this request
                    App::setLocale($currentLocale);
                }
            };

            Event::listen('cms.template.save', $handler);
            Event::listen('pages.object.save', $handler);
        }
    }

    public function registerComponents()
    {
        return [
           'RainLab\Translate\Components\LocalePicker' => 'localePicker',
           'RainLab\Translate\Components\AlternateHrefLangElements' => 'alternateHrefLangElements'
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
                '__' => [$this, 'translatePlural'],
                'transRaw'  => [$this, 'translateRawString'],
                'transRawPlural' => [$this, 'translateRawPlural'],
                'localeUrl' => [$this, 'localeUrl'],
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
            'RainLab\Translate\FormWidgets\MLMediaFinder' => 'mlmediafinder',
        ];
    }

    protected function registerAssetBundles()
    {
        CombineAssets::registerCallback(function ($combiner) {
            $combiner->registerBundle('$/rainlab/translate/assets/less/messages.less');
            $combiner->registerBundle('$/rainlab/translate/assets/less/multilingual.less');
        });
    }

    public function localeUrl($url, $locale)
    {
        $translator = Translator::instance();
        $parts = parse_url($url);
        $path = array_get($parts, 'path');
        return http_build_url($parts, [
            'path' => '/' . $translator->getPathInLocale($path, $locale)
        ]);
    }

    public function translateString($string, $params = [], $locale = null)
    {
        return Message::trans($string, $params, $locale);
    }

    public function translatePlural($string, $count = 0, $params = [], $locale = null)
    {
        return Lang::choice(Message::trans($string, $params, $locale), $count, $params);
    }

    public function translateRawString($string, $params = [], $locale = null)
    {
        return Message::transRaw($string, $params, $locale);
    }

    public function translateRawPlural($string, $count = 0, $params = [], $locale = null)
    {
        return Lang::choice(Message::transRaw($string, $params, $locale), $count, $params);
    }
}
