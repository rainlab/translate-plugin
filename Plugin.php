<?php namespace RainLab\Translate;

use App;
use Lang;
use File;
use Event;
use Backend;
use Cms\Classes\Page;
use Cms\Classes\Content;
use System\Classes\PluginBase;
use RainLab\Translate\Models\Message;
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
            'icon'        => 'icon-language'
        ];
    }

    public function boot()
    {
        /*
         * Set the page context for translation caching.
         * Adds language suffixes to page files.
         */
        Event::listen('cms.page.beforeDisplay', function($controller, $url, $page) {
            if (!$page) {
                return;
            }

            $translator = Translator::instance();
            $translator->loadLocaleFromSession();
            Message::setContext($translator->getLocale(), $page->url);

            $defaultLocale = $translator->getDefaultLocale();
            $locale = $translator->getLocale();

            $fileName = $page->getFileName();
            $fileName = str_replace(strstr($fileName, "."), '', $fileName);

            if (!strlen(File::extension($fileName))) {
                $fileName .= '.htm';
            }

            /*
             * Splice the active locale in to the filename
             * - page.htm -> page.en.htm
             */
            if ($locale != $defaultLocale) {
                $fileName = substr_replace($fileName, '.' . $locale, strrpos($fileName, '.'), 0);
                $page->setFileName($fileName);
            }

            $page = Page::loadCached($controller->getTheme(), $fileName);
            return $page;
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
         * Automatically replace form fields for multi lingual equivalents
         */
        Event::listen('backend.form.extendFieldsBefore', function($widget) {

            if (!$model = $widget->model)
                return;

            if (!method_exists($model, 'isClassExtendedWith'))
                return;

            if (!$model->isClassExtendedWith('RainLab.Translate.Behaviors.TranslatableModel'))
                return;

            if (!is_array($model->translatable))
                return;

            if (!empty($widget->config->fields))
                $widget->config->fields = $this->processFormMLFields($widget->config->fields, $model);

            if (!empty($widget->config->tabs['fields']))
                $widget->config->tabs['fields'] = $this->processFormMLFields($widget->config->tabs['fields'], $model);

            if (!empty($widget->config->secondaryTabs['fields']))
                $widget->config->secondaryTabs['fields'] = $this->processFormMLFields($widget->config->secondaryTabs['fields'], $model);
        });
    }

    public function registerComponents()
    {
        return [
           'RainLab\Translate\Components\LocalePicker' => 'localePicker',
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
            ],
            'messages' => [
                'label'       => 'rainlab.translate::lang.messages.title',
                'description' => 'rainlab.translate::lang.messages.description',
                'icon'        => 'icon-list-alt',
                'url'         => Backend::url('rainlab/translate/messages'),
                'order'       => 551,
                'category'    => 'rainlab.translate::lang.plugin.name',
            ],
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
                'alias' => 'mlricheditor'
            ],
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
     * Helper function to replace standard fields with multi lingual equivalents
     * @param  array $fields
     * @param  Model $model
     * @return array
     */
    protected function processFormMLFields($fields, $model)
    {
        foreach ($fields as $name => $config) {
            if (!in_array($name, $model->translatable))
                continue;

            $type = array_get($config, 'type', 'text');
            if ($type == 'text')
                $fields[$name]['type'] = 'mltext';
            elseif ($type == 'textarea')
                $fields[$name]['type'] = 'mltextarea';
            elseif ($type == 'richeditor')
                $fields[$name]['type'] = 'mlricheditor';
        }

        return $fields;
    }

}
