<?php namespace Rainlab\Translate\Components;

use Event;
use RainLab\Translate\Classes\Translator;
use RainLab\Translate\Models\Locale as LocaleModel;
use October\Rain\Router\Router as RainRouter;
use Cms\Classes\ComponentBase;

/**
 * AlternateHrefLangElements
 */
class AlternateHrefLangElements extends ComponentBase
{
    /**
     * componentDetails
     */
    public function componentDetails()
    {
        return [
            'name' => 'rainlab.translate::lang.alternate_hreflang.component_name',
            'description' => 'rainlab.translate::lang.alternate_hreflang.component_description'
        ];
    }

    /**
     * locales
     */
    public function locales()
    {
        // Available locales
        $locales = collect(LocaleModel::listEnabled());

        // Transform it to contain the new urls
        $locales->transform(function ($item, $key) {
            return $this->retrieveLocalizedUrl($key);
        });

        return $locales->toArray();
    }

    /**
     * retrieveLocalizedUrl
     */
    protected function retrieveLocalizedUrl($locale)
    {
        $translator = Translator::instance();
        $page = $this->getPage();

        /*
         * Static Page
         */
        if (isset($page->apiBag['staticPage'])) {
            $staticPage = $page->apiBag['staticPage'];
            $staticPage->rewriteTranslatablePageUrl($locale);
            $localeUrl = array_get($staticPage->attributes, 'viewBag.url');
        }
        /*
         * CMS Page
         */
        else {
            $page->rewriteTranslatablePageUrl($locale);
            $params = $this->getRouter()->getParameters();

            $translatedParams = Event::fire('translate.localePicker.translateParams', [
                $page,
                $params,
                $this->oldLocale,
                $locale
            ], true);

            if ($translatedParams) {
                $params = $translatedParams;
            }

            $router = new RainRouter;
            $localeUrl = $router->urlFromPattern($page->url, $params);
        }

        return $translator->getPathInLocale($localeUrl, $locale);
    }

}
