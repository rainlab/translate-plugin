<?php namespace RainLab\Translate\Classes;

use Str;
use App;
use Event;
use System\Classes\PluginManager;
use RainLab\Translate\Classes\Locale as LocaleModel;
use October\Rain\Html\Helper as HtmlHelper;

/**
 * EventPluginRegistry for bootstrapping events related to plugins,
 * mostly the Static Pages plugin.
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class EventPluginRegistry
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * registerEvents
     */
    public function registerEvents()
    {
        $this->extendStaticPagesCmsSitePicker();
        $this->extendStaticPagesBackendFormFields();
        $this->extendStaticMenusBackendFormFields();
    }

    /**
     * bootEvents
     */
    public function bootEvents()
    {
        $this->extendStaticPagesMenuReferences();
        $this->extendStaticPagesTemplateList();
    }

    /**
     * extendStaticPagesCmsSitePicker changes the sitepicker to support translated static page URLs
     */
    protected function extendStaticPagesCmsSitePicker()
    {
        Event::listen('cms.sitePicker.overridePattern', function($page, $pattern, $currentSite, $proposedSite) {
            if (isset($page->apiBag['staticPage'])) {
                $staticPage = $page->apiBag['staticPage'];
                if ($staticPage->hasTranslatablePageUrl($proposedSite->hard_locale)) {
                    return $staticPage->getSettingsUrlAttributeTranslated($proposedSite->hard_locale);
                }
                else {
                    return $staticPage->getOriginalUrlAttributeTranslated();
                }
            }
        });
    }

    /**
     * registerFormFieldAdjustments for RainLab.Pages MenuItem data
     */
    protected function extendStaticPagesBackendFormFields()
    {
        // Make the URL field translatable since it has a custom behavior
        Event::listen('backend.form.extendFieldsBefore', function($widget) {
            if (!PluginManager::instance()->exists('RainLab.Pages')) {
                return;
            }

            if (!$widget->model instanceof \RainLab\Pages\Classes\Page) {
                return;
            }

            if ($widget->isNested) {
                return;
            }

            $widget->fields['viewBag[url]']['translatable'] = true;
        }, -1);

        // Load Page URL
        Event::listen('pages.object.load', function($controller, $template, $type) {
            if ($type === 'page') {
                $template->rewriteTranslatablePageUrl();
                $template->viewBag['url'] = array_get($template->attributes, 'viewBag.url');
            }
        });

        // Save Page URL
        Event::listen('pages.object.fillObject', function($controller, $template, &$data, $type) {
            if ($type === 'page') {
                $locale = $template->translateContext();
                $originalData = $template->getOriginal();
                $localeUrls = $originalData['viewBag']['localeUrl'] ?? [];

                // Handle translated URL
                if ($template->shouldTranslate()) {
                    $wantUrl = $data['settings']['viewBag']['url'] ?? '';
                    $haveUrl = $originalData['viewBag']['url'] ?? $wantUrl;
                    if ($wantUrl != $haveUrl) {
                        $localeUrls[$locale] = $wantUrl;
                    }
                    else {
                        unset($localeUrls[$locale]);
                    }

                    array_set($data, 'settings.viewBag.url', $haveUrl);
                }

                array_set($data, 'settings.viewBag.localeUrl', $localeUrls);
            }
        });

        // Save View Bag
        Event::listen('pages.object.fillObject', function($controller, $template, &$data, $type) {
            if ($type === 'page' && $template->shouldTranslate()) {
                $locale = $template->translateContext();
                $originalData = $template->getOriginal();

                // Set the translated values to the model
                foreach ($template->getTranslatableAttributes() as $key) {
                    $dotKey = implode('.', HtmlHelper::nameToArray($key));
                    $studKey = Str::studly(implode(' ', HtmlHelper::nameToArray($key)));
                    $mutateMethod = 'set'.$studKey.'AttributeTranslated';

                    // Only work with view bag and placeholders
                    if (!starts_with($dotKey, 'viewBag.') && !starts_with($dotKey, 'placeholders.')) {
                        continue;
                    }

                    // Locate translated value, pull and replace with original value
                    if (starts_with($dotKey, 'viewBag.')) {
                        $value = array_get($data['settings'], $dotKey);
                        array_set($data['settings'], $dotKey, array_get($originalData, $dotKey, $value));
                    }
                    else {
                        $value = array_get($data, $dotKey);
                        array_set($data, $dotKey, array_get($originalData, $dotKey, $value));
                    }

                    // Determine if this is worth saving
                    if (starts_with($dotKey, 'placeholders.') && !trim((string) $value)) {
                        continue;
                    }

                    if ($value === null) {
                        continue;
                    }

                    // Store translated value
                    if ($this->objectMethodExists($template, $mutateMethod)) {
                        $template->$mutateMethod($value, $locale);
                    }
                    elseif ($this->objectMethodExists($template, 'setAttributeTranslated')) {
                        $template->setAttributeTranslated($key, $value, $locale);
                    }
                }
            }
        });
    }

    /**
     * extendStaticMenusBackendFormFields
     */
    protected function extendStaticMenusBackendFormFields()
    {
        $fieldsToTranslate = ['title', 'url'];

        // Defer event with low priority to let others contribute before this registers.
        Event::listen('backend.form.extendFieldsBefore', function($widget) use ($fieldsToTranslate) {
            // Handle RainLab.Pages MenuItem translations
            if (!PluginManager::instance()->exists('RainLab.Pages')) {
                return;
            }

            if (!$widget->model instanceof \RainLab\Pages\Classes\MenuItem) {
                return;
            }

            // Replace specified fields with multilingual versions
            foreach ($fieldsToTranslate as $fieldName) {
                $widget->fields[$fieldName]['translatable'] = true;
            }
        }, -1);

        // Load Menu Fields
        Event::listen('pages.object.load', function($controller, $template, $type) use ($fieldsToTranslate) {
            if ($type === 'menu' && ($locale = Translator::instance()->getLocale())) {

                $iterator = function(&$items) use ($locale, $fieldsToTranslate, &$iterator) {
                    foreach ($items as &$item) {
                        foreach ($fieldsToTranslate as $fieldName) {
                            if (isset($item[$fieldName])) {
                                $item[$fieldName] = array_get($item['viewBag'], "locale.$locale.$fieldName", $item[$fieldName]);
                            }
                        }

                        if (isset($item['items']) && is_array($item['items'])) {
                            $iterator($item['items']);
                        }
                    }
                };

                if (isset($template->attributes['items'])) {
                    $iterator($template->attributes['items']);
                }
            }
        });

        // Save View Bag
        Event::listen('pages.object.fillObject', function($controller, $template, &$data, $type) use ($fieldsToTranslate) {
            if ($type === 'menu' && ($locale = Translator::instance()->getLocale())) {
                $originalData = $template->getOriginal()['items'] ?? [];

                $iterator = function($templateItem, &$postItem) use ($locale, $fieldsToTranslate, &$iterator) {
                    $localeData = array_get($templateItem['viewBag'] ?? [], 'locale', []);

                    foreach ($fieldsToTranslate as $fieldName) {
                        // Locate translated value
                        $value = array_get($postItem, $fieldName);
                        if ($value === null) {
                            continue;
                        }

                        // Set the default locale value
                        $originalValue = array_get($templateItem, $fieldName, $value);

                        if ($this->objectShouldTranslate()) {
                            array_set($postItem, $fieldName, $originalValue);
                            $localeData[$locale][$fieldName] = $value;
                        }
                    }

                    if ($localeData) {
                        array_set($postItem['viewBag'], 'locale', $localeData);
                    }

                    if (isset($postItem['items']) && is_array($postItem['items'])) {
                        foreach ($postItem['items'] as $childIndex => &$childItem) {
                            $childTemplateItem = $templateItem['items'][$childIndex] ?? [];
                            $iterator($childTemplateItem, $childItem);
                        }
                    }
                };

                foreach ($data['itemData'] as $index => &$item) {
                    $originalItem = $originalData[$index] ?? [];
                    $iterator($originalItem, $item);
                }
            }
        });
    }

    /**
     * extendStaticPagesMenuReferences populates MenuItem properties with localized values if available
     */
    protected function extendStaticPagesMenuReferences()
    {
        Event::listen('pages.menu.referencesGenerated', function (&$items) {
            $locale = App::getLocale();
            $iterator = function ($menuItems) use (&$iterator, $locale) {
                $result = [];
                foreach ($menuItems as $item) {
                    $localeFields = array_get($item->viewBag, "locale.$locale", []);
                    foreach ($localeFields as $fieldName => $fieldValue) {
                        if ($fieldValue) {
                            $item->$fieldName = $fieldValue;
                        }
                    }
                    if ($item->items) {
                        $item->items = $iterator($item->items);
                    }
                    $result[] = $item;
                }
                return $result;
            };
            $items = $iterator($items);
        });
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

    /**
     * objectShouldTranslate is a helper function to determine if translations are used
     * and if so, returns the active locale
     */
    protected function objectShouldTranslate(): string
    {
        $translate = Translator::instance();
        $locale = $translate->getLocale();
        $localeDefault = $translate->getDefaultLocale();
        $shouldTranslate = $locale !== $localeDefault;
        if (!$shouldTranslate) {
            return '';
        }

        return $locale;
    }

    /**
     * objectMethodExists is an internal helper for method existence checks.
     *
     * @param  object $object
     * @param  string $method
     * @return boolean
     */
    protected function objectMethodExists($object, $method)
    {
        if (method_exists($object, 'methodExists')) {
            return $object->methodExists($method);
        }

        return method_exists($object, $method);
    }
}
