<?php namespace RainLab\Translate\Classes\EventRegistry;

use Str;
use Event;
use RainLab\Translate\Classes\Locale as LocaleModel;

/**
 * TemplateListHandler prunes localized content files from template lists
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class TemplateListHandler
{
    /**
     * register events
     */
    public function register()
    {
    }

    /**
     * boot events
     */
    public function boot()
    {
        $this->extendStaticPagesTemplateList();
    }

    /**
     * extendStaticPagesTemplateList prunes localized content files from template list
     */
    protected function extendStaticPagesTemplateList()
    {
        Event::listen('pages.content.templateList', function($widget, $templates) {
            $locales = LocaleModel::listAvailable();

            $extensions = array_map(function($ext) {
                return '.'.$ext;
            }, array_keys($locales));

            return $templates->filter(function($template) use ($extensions) {
                return !Str::endsWith($template->getBaseFileName(), $extensions);
            });
        });
    }
}
