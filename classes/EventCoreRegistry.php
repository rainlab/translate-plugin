<?php namespace RainLab\Translate\Classes;

use App;
use Str;
use File;
use Event;
use Cms\Classes\Page;
use Cms\Classes\Content;
use System\Classes\MailManager;
use RainLab\Translate\Models\Message;
use RainLab\Translate\Classes\Locale as LocaleModel;
use RainLab\Translate\Classes\Translator;
use RainLab\Translate\Classes\ThemeScanner;
use Exception;

/**
 * EventCoreRegistry for bootstrapping events
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class EventCoreRegistry
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * registerEvents
     */
    public function registerEvents()
    {
        $this->extendCmsSitePicker();
        $this->extendCmsPageObject();
        $this->extendEditorPageToolbar();
        $this->extendCmsThemeDataModel();
        $this->extendBackendFormFields();
        $this->extendSystemMailerContent();
    }

    /**
     * bootEvents
     */
    public function bootEvents()
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
     * registerFormFieldAdjustments extends the backend form fields
     */
    protected function extendBackendFormFields()
    {
        // Defer event with low priority to let others contribute before this registers.
        Event::listen('backend.form.extendFieldsBefore', function($widget) {
            if ($widget->isNested) {
                return;
            }

            if (!$model = $widget->model) {
                return;
            }

            if (!method_exists($model, 'isClassExtendedWith')) {
                return;
            }

            if (
                !$model->isClassExtendedWith(\RainLab\Translate\Behaviors\TranslatableModel::class) &&
                !$model->isClassExtendedWith(\RainLab\Translate\Behaviors\TranslatablePage::class) &&
                !$model->isClassExtendedWith(\RainLab\Translate\Behaviors\TranslatableCmsObject::class)
            ) {
                return;
            }

            if (!$model->hasTranslatableAttributes()) {
                return;
            }

            if (!empty($widget->fields)) {
                $widget->fields = $this->processFormMLFields($widget->fields, $model);
            }

            if (!empty($widget->tabs['fields'])) {
                $widget->tabs['fields'] = $this->processFormMLFields($widget->tabs['fields'], $model);
            }

            if (!empty($widget->secondaryTabs['fields'])) {
                $widget->secondaryTabs['fields'] = $this->processFormMLFields($widget->secondaryTabs['fields'], $model);
            }
        }, -1);
    }

    /**
     * processFormMLFields function to flag multilingual fields as translatable
     * @param  array $fields
     * @param  Model $model
     * @return array
     */
    protected function processFormMLFields($fields, $model)
    {
        $translatable = array_flip($model->getTranslatableAttributes());

        // Special: A custom field "markup_html" is used for Content templates.
        // @todo review if this is still needed -sg
        if ($model instanceof Content && array_key_exists('markup', $translatable)) {
            $translatable['markup_html'] = true;
        }

        foreach ($fields as $name => $config) {
            if (!array_key_exists($name, $translatable)) {
                continue;
            }

            $fields[$name]['translatable'] = true;
        }

        return $fields;
    }

    /**
     * extendSystemMailerContent loads localized version of mail templates (akin to localized CMS content files)
     */
    protected function extendSystemMailerContent()
    {
        Event::listen('mailer.beforeAddContent', function ($mailer, $message, $view, $data, $raw, $plain) {
            // Raw content cannot be localized at this level
            if (!empty($raw)) {
                return;
            }

            // Get the locale to use for this template
            $locale = !empty($data['_current_locale']) ? $data['_current_locale'] : App::getLocale();

            $factory = $mailer->getViewFactory();

            if (!empty($view)) {
                $view = $this->getLocalizedView($factory, $view, $locale);
            }

            if (!empty($plain)) {
                $plain = $this->getLocalizedView($factory, $plain, $locale);
            }

            $code = $view ?: $plain;
            if (empty($code)) {
                return null;
            }

            $plainOnly = empty($view);

            // Caller firing the event is expecting a FALSE response to halt the event
            if (MailManager::instance()->addContentToMailer($message, $code, $data, $plainOnly)) {
                return false;
            }
        }, 1);
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

    //
    // Theme
    //

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

    //
    // CMS objects
    //

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

    /**
     * getLocalizedView searches mail view files based on locale
     * @param  \October\Rain\Mail\Mailer $mailer
     * @param  \Illuminate\Mail\Message $message
     * @param  string $code
     * @param  string $locale
     * @return string|null
     */
    public function getLocalizedView($factory, $code, $locale)
    {
        $locale = strtolower($locale);
        $searchPaths[] = $locale;

        if (str_contains($locale, '-')) {
            list($lang) = explode('-', $locale);
            $searchPaths[] = $lang;
        }

        foreach ($searchPaths as $path) {
            $localizedView = sprintf('%s-%s', $code, $path);

            if ($factory->exists($localizedView)) {
                return $localizedView;
            }
        }

        return null;
    }
}
