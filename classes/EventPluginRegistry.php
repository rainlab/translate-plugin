<?php namespace RainLab\Translate\Classes;

use Str;
use App;
use Event;
use System\Classes\PluginManager;
use October\Rain\Html\Helper as HtmlHelper;

/**
 * EventPluginRegistry for bootstrapping events related to plugins
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
    }

    /**
     * bootEvents
     */
    public function bootEvents()
    {
        $this->extendStaticPagesMenuReferences();
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
        // Defer event with low priority to let others contribute before this registers.
        Event::listen('backend.form.extendFieldsBefore', function($widget) {
            // Handle RainLab.Pages MenuItem translations
            if (!PluginManager::instance()->exists('RainLab.Pages')) {
                return;
            }

            $fieldsToTranslate = [];
            if ($widget->model instanceof \RainLab\Pages\Classes\Page) {
                $fieldsToTranslate = ['viewBag[url]'];
            }

            if ($widget->model instanceof \RainLab\Pages\Classes\MenuItem) {
                $fieldsToTranslate = ['title', 'url'];
            }

            // Replace specified fields with multilingual versions
            foreach ($fieldsToTranslate as $fieldName) {
                $widget->fields[$fieldName]['translatable'] = true;
            }
        }, -1);

        Event::listen('pages.object.load', function($controller, $template, $type) {
            if ($type === 'page') {
                $template->rewriteTranslatablePageUrl();
                $template->viewBag['url'] = array_get($template->attributes, 'viewBag.url');
            }
        });

        Event::listen('pages.object.fillObject', function($controller, $template, &$data, $type) {
            if ($type === 'page') {
                $locale = Translator::instance()->getLocale();

                // Set the translated values to the model
                foreach ($template->getTranslatableAttributes() as $key) {
                    $dotKey = implode('.', HtmlHelper::nameToArray($key));
                    $studKey = Str::studly(implode(' ', HtmlHelper::nameToArray($key)));
                    $mutateMethod = 'set'.$studKey.'AttributeTranslated';

                    $value = starts_with($dotKey, 'viewBag.')
                        ? array_pull($data['settings'], $dotKey, -1)
                        : array_pull($data, $dotKey, -1);

                    if ($value === -1) {
                        continue;
                    }

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
