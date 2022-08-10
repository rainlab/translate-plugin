<?php namespace RainLab\Translate\Classes;

use App;
use Event;
use System\Classes\PluginManager;

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

            if ($widget->model instanceof \RainLab\Pages\Classes\MenuItem) {
                $fieldsToTranslate = ['title', 'url'];

                // Replace specified fields with multilingual versions
                foreach ($fieldsToTranslate as $fieldName) {
                    $widget->fields[$fieldName]['translatable'] = true;
                }
            }
        }, -1);
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
}
