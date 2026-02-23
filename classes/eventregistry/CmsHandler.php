<?php namespace RainLab\Translate\Classes\EventRegistry;

use File;
use Event;
use Cms\Classes\Page;
use Cms\Classes\Content;
use RainLab\Translate\Models\Message;
use RainLab\Translate\Classes\Locale as LocaleModel;
use RainLab\Translate\Classes\Translator;
use RainLab\Translate\Classes\ThemeScanner;
use Exception;

/**
 * CmsHandler for CMS-related translation events
 *
 * @package rainlab\translate
 * @author Alexey Bobkov, Samuel Georges
 */
class CmsHandler
{
    /**
     * register events
     */
    public function register()
    {
        $this->extendCmsSitePicker();
        $this->extendCmsPageObject();
        $this->extendEditorPageToolbar();
        $this->extendCmsThemeDataModel();
    }

    /**
     * boot events
     */
    public function boot()
    {
        $this->extendCmsContentObject();
    }

    /**
     * extendCmsSitePicker changes the sitepicker to support translated url patterns
     */
    protected function extendCmsSitePicker()
    {
        Event::listen('cms.sitePicker.overridePattern', function($page, $pattern, $currentSite, $proposedSite) {
            if ($page->hasTranslatablePageUrl($proposedSite->hard_locale)) {
                return $page->getSettingsUrlAttributeTranslated($proposedSite->hard_locale);
            }
            else {
                return $page->getOriginalUrlAttributeTranslated();
            }
        }, -1);
    }

    /**
     * extendCmsPageObject handles translated page URLs
     */
    protected function extendCmsPageObject()
    {
        Page::extend(function($model) {
            if (!$model->propertyExists('translatable')) {
                $model->addDynamicProperty('translatable', []);
            }

            $model->translatable = array_merge($model->translatable, ['title', 'description', 'meta_title', 'meta_description']);

            if (!$model->isClassExtendedWith(\RainLab\Translate\Behaviors\TranslatablePageUrl::class)) {
                $model->extendClassWith(\RainLab\Translate\Behaviors\TranslatablePageUrl::class);
            }

            if (!$model->isClassExtendedWith(\RainLab\Translate\Behaviors\TranslatablePage::class)) {
                $model->extendClassWith(\RainLab\Translate\Behaviors\TranslatablePage::class);
            }
        });
    }

    /**
     * extendEditorPageToolbar adds the translate button to editor
     */
    protected function extendEditorPageToolbar()
    {
        Event::listen('cms.template.getTemplateToolbarSettingsButtons', function($extension, $dataHolder) {
            if ($dataHolder->templateType !== 'page') {
                return;
            }

            if (!LocaleModel::isAvailable()) {
                return;
            }

            $locales = LocaleModel::listAvailable();
            $defaultLocale = LocaleModel::getDefault()->code ?? null;

            $properties = [];
            foreach ($locales as $locale => $label) {
                if ($locale == $defaultLocale) {
                    continue;
                }

                $properties[] = [
                    'property' => 'localeUrl.'.$locale,
                    'title' => 'cms::lang.editor.url',
                    'tab' => $label,
                    'type' => 'string',
                ];

                $properties[] = [
                    'property' => 'localeTitle.'.$locale,
                    'title' => 'cms::lang.editor.title',
                    'tab' => $label,
                    'type' => 'string',
                ];

                $properties[] = [
                    'property' => 'localeDescription.'.$locale,
                    'title' => 'cms::lang.editor.description',
                    'tab' => $label,
                    'type' => 'text',
                ];

                $properties[] = [
                    'property' => 'localeMeta_title.'.$locale,
                    'title' => 'cms::lang.editor.meta_title',
                    'tab' => $label,
                    'type' => 'string',
                ];

                $properties[] = [
                    'property' => 'localeMeta_description.'.$locale,
                    'title' => 'cms::lang.editor.meta_description',
                    'tab' => $label,
                    'type' => 'text',
                ];
            }

            $dataHolder->buttons[] = [
                'button' => 'Translate',
                'icon' => 'octo-icon-globe',
                'popupTitle' => 'Translate Page Properties',
                'properties' => $properties
            ];
        });
    }

    /**
     * extendCmsThemeDataModel adds translation support to the theme data model
     */
    protected function extendCmsThemeDataModel()
    {
        Event::listen('cms.theme.createThemeDataModel', function($attributes) {
            return new \RainLab\Translate\Models\MLThemeData($attributes);
        });
    }

    /**
     * extendCmsContentObject adds language suffixes to content files.
     */
    protected function extendCmsContentObject()
    {
        Event::listen('cms.page.beforeRenderContent', function($controller, $fileName) {
            if (!strlen(File::extension($fileName))) {
                $fileName .= '.htm';
            }

            // Splice the active locale in to the filename
            // - content.htm -> content.en.htm
            $locale = Translator::instance()->getLocale();
            $fileName = substr_replace($fileName, '.'.$locale, strrpos($fileName, '.'), 0);
            if (($content = Content::loadCached($controller->getTheme(), $fileName)) !== null) {
                return $content;
            }
        });
    }

    /**
     * importMessagesFromTheme
     */
    public function importMessagesFromTheme($themeCode)
    {
        try {
            (new ThemeScanner)->scanThemeConfigForMessages($themeCode);
        }
        catch (Exception $ex) {}
    }

    /**
     * setMessageContext for translation caching.
     */
    public function setMessageContext($page)
    {
        if (!$page) {
            return;
        }

        $translator = Translator::instance();

        Message::setContext($translator->getLocale(), $page->url);
    }
}
