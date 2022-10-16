# Upgrading from Translate v1 to v2

This guide can be used to help migrate from RainLab.Translate v1 to v2. It should be straight forward process without any major disruptions.

Beginning from October CMS v3.1, a multisite feature was introduced that supercedes many functions provided by the RainLab.Translate plugin. The existing data structures are preserved so all translations are carried across and no "retranslation" or modifying existing plugins should be needed.

## Upgrade Instructions

1. Run `php artisan plugin:install rainlab.translate` to request the latest version (you do not need to uninstall v1 first).

1. Navigate to **System → Sites** and create a site for each locale used by the website.

1. Replace the `localePicker` and `alternateHrefLangElements` components used in the front end (see below).

1. Migrate theme messages using `php artisan translate:migratev1` (non destructive).

1. Continue using this plugin as normal.

## Key Differences

- The "Languages" settings area is replaced by the "Sites" settings area. This is where available sites are defined, including the available languages.

- The per-field language picker is replaced by a per-site language picker. The translator selects the active language globally and can perform comparisons using multiple browser tabs.

- Storing the locale in the session is no longer used and must be determined by the site definition, as a hostname or route prefix.

## Key Similarities

- Models are still translated using the `RainLab\Translate\Behaviors\TranslatableModel`.

- Theme translation is still managed by this plugin.

## Breaking Changes

### Locale model is replaced

Since there is no Languages settings page, the `RainLab\Translate\Models\Locale` model is replaced by the `RainLab\Translate\Classes\Locale` class.

### Middleware class is replaced

If using PHP routes to determine locale, the `RainLab\Translate\Classes\LocaleMiddleware` class is replaced by the `System\Middleware\ActiveSite` middleware class.

### CMS Components replaced

The `localePicker` and `alternateHrefLangElements` components provided by this plugin have been replaced by the `sitePicker` component.

Here is the sample code to replace the `localePicker` component:

```twig
<select class="form-control" onchange="window.location.assign(this.value)">
    {% for site in sitePicker.sites %}
        <option value="{{ site.url }}" {{ this.site.code == site.code ? 'selected' }}>{{ site.name }}</option>
    {% endfor %}
</select>
```

Here is the sample code to replace the `alternateHrefLangElements` component:

```twig
{% for site in sitePicker.sites %}
    <link rel="alternate" hreflang="{{ site.locale }}" href="{{ site.url }}" />
{% endfor %}
```

### Events Updated

The `translate.localePicker.translateQuery` event has been replaced by the `cms.sitePicker.overrideQuery`. The arguments are the same except the site definition is passed instead of the locale code, use the `hard_locale` attribute of the site definition to obtain the locale.

```php
Event::listen('cms.sitePicker.overrideQuery', function($page, $params, $currentSite, $proposedSite) {
    if ($page->baseFileName == 'your-page-filename') {
        return YourModel::translateQuery($params, $currentSite->hard_locale, $proposedSite->hard_locale);
    }
});
```

The `translate.localePicker.translateParams` event has been replaced by the `cms.sitePicker.overrideParams` event. The arguments are the same except the site definition is passed instead of the locale code, use the `hard_locale` attribute of the site definition to obtain the locale.

```php
Event::listen('cms.sitePicker.overrideParams', function($page, $params, $currentSite, $proposedSite) {
    if ($page->baseFileName == 'your-page-filename') {
        return YourModel::overrideParams($params, $currentSite->hard_locale, $proposedSite->hard_locale);
    }
});
```
