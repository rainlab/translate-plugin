# Translation plugin

Enables multi-lingual sites.

## Selecting a Language

Different languages can be set up in the admin panel using the **Settings → Sites** area. Each site should use a different locale to be considered a language.

The visitor can select a language by prefixing the language code to the URL or using a dedicated hostname. For example:

* `http://website.tld/` will display the site in the default language
* `http://website.tld/ru/` will display the site in Russian
* `http://website.tld/fr/` will display the site in French

### License

This plugin is an official extension of the October CMS platform and is free to use if you have a platform license. See [EULA license](LICENSE.md) for more details.

### Installation

To install using October CMS v3.1 or above:

```
php artisan plugin:install rainlab.translate
```

To install using October CMS v3.0 and below:

```
php artisan plugin:install rainlab.translate --want="^1.0"
```

### Upgrading from v1 to v2

If you are upgrading from version 1 of this plugin, [view the upgrade guide](https://github.com/rainlab/translate-plugin/blob/master/UPGRADE.md).

## Language Picker Component

A visitor can select their chosen language using the native `SitePicker` component that is included in the October CMS core. This component will display a simple dropdown that changes the page language depending on the selection.

```twig
title = "Home"
url = "/"

[sitePicker]
==

<h3>{{ 'Please select your language:'|_ }}</h3>
<select class="form-control" onchange="window.location.assign(this.value)">
    {% for site in sitePicker.sites %}
        <option value="{{ site.url }}" {{ this.site.code == site.code ? 'selected' }}>{{ site.name }}</option>
    {% endfor %}
</select>
```

If translated, the text above will appear as whatever language is selected by the user. The dropdown is very basic and is intended to be restyled. A simpler example might be:

```twig
<p>
    Switch language to:

    {% for site in sitePicker.sites %}
        <a href="{{ site.url }}">{{ site.name }}</a>
    {% endfor %}
</p>
```

## Message Translation

Message or string translation is the conversion of adhoc strings used throughout the site. A message can be translated with parameters.

```twig
{{ 'site.name'|_ }}

{{ 'Welcome to our website!'|_ }}

{{ 'Hello :name!'|_({ name: 'Friend' }) }}
```

A message can also be translated for a choice usage.

```twig
{{ 'There are no apples|There are :number applies!'|__(2, { number: 'two' }) }}
```

Or you set a locale manually by passing a second argument.

```twig
{{ 'this is always english'|_({}, 'en') }}
```

Themes can provide default values for these messages by defining a `translate` key in the `theme.yaml` file, located in the theme directory.

```yaml
name: My Theme
# [...]

translate:
    en:
        site.name: 'My Website'
        nav.home: 'Home'
        nav.video: 'Video'
        title.home: 'Welcome Home'
        title.video: 'Screencast Video'
```

You may also define the translations in a separate file, where the path is relative to the theme. The following definition will source the default messages from the file **config/lang.yaml** inside the theme.

```yaml
name: My Theme
# [...]

translate: config/lang.yaml
```

This is an example of **config/lang.yaml** file with two languages:

```yaml
en:
    site.name: 'My Website'
    nav.home: 'Home'
    nav.video: 'Video'
    title.home: 'Welcome Home'
hr:
    site.name: 'Moje web stranice'
    nav.home: 'Početna'
    nav.video: 'Video'
    title.home: 'Dobrodošli'
```

You may also define the translations in a separate file per locale, where the path is relative to the theme. The following definition will source the default messages from the file **config/lang-en.yaml** inside the theme for the english locale and from the file **config/lang-fr.yaml** for the french locale.

```yaml
name: My Theme
# [...]

translate:
    en: config/lang-en.yaml
    fr: config/lang-fr.yaml
```

This is an example for the **config/lang-en.yaml** file:

```yaml
site.name: 'My Website'
nav.home: 'Home'
nav.video: 'Video'
title.home: 'Welcome Home'
```

In order to make these default values reflected to your frontend site, go to **Settings -> Translate messages** in the backend and hit **Scan for messages**. They will also be loaded automatically when the theme is activated.

The same operation can be performed with the `translate:scan` artisan command. It may be worth including it in a deployment script to automatically fetch updated messages:

```bash
php artisan translate:scan
```

Add the `--purge` option to clear old messages first:

```bash
php artisan translate:scan --purge
```

## Content & Mail Template Translation

This plugin activates a feature in the CMS that allows content & mail template files to use language suffixes, for example:

* **welcome.htm** will contain the content or mail template in the default language.
* **welcome.ru.htm** will contain the content or mail template in Russian.
* **welcome.fr.htm** will contain the content or mail template in French.

## Model Translation

Models can have their attributes translated by using the `RainLab\Translate\Behaviors\TranslatableModel` behavior and specifying which attributes to translate in the class.

```php
class User
{
    public $implement = [
        \RainLab\Translate\Behaviors\TranslatableModel::class
    ];

    public $translatable = ['name'];
}
```

The attribute will then contain the default language value and other language code values can be created by using the `translateContext()` method.

```php
$user = User::first();

// Outputs the name in the default language
echo $user->name;

$user->translateContext('fr');

// Outputs the name in French
echo $user->name;
```

You may use the same process for setting values.

```php
$user = User::first();

// Sets the name in the default language
$user->name = 'English';

$user->translateContext('fr');

// Sets the name in French
$user->name = 'Anglais';
```

The `lang()` method is a shorthand version of `translateContext()` and is also chainable.

```php
// Outputs the name in French
echo $user->lang('fr')->name;
```

This can be useful inside a Twig template.

```twig
{{ user.lang('fr').name }}
```

There are ways to get and set attributes without changing the context.

```php
// Gets a single translated attribute for a language
$user->getAttributeTranslated('name', 'fr');

// Sets a single translated attribute for a language
$user->setAttributeTranslated('name', 'Jean-Claude', 'fr');
```

## Theme Data Translation

It is also possible to translate theme customisation options. Just mark your form fields with `translatable` property and the plugin will take care about everything else:

```yaml
tabs:
    fields:
        website_name:
            tab: Info
            label: Website Name
            type: text
            default: Your website name
            translatable: true
```

## Fallback Attribute Values

By default, untranslated attributes will fall back to the default locale. This behavior can be disabled by calling the `noFallbackLocale` method.

```php
$user = User::first();

$user->noFallbackLocale()->lang('fr');

// Returns NULL if there is no French translation
$user->name;
```

## Indexed Attributes

Translatable model attributes can also be declared as an index by passing the `$transatable` attribute value as an array. The first value is the attribute name, the other values represent options, in this case setting the option `index` to `true`.

```php
public $translatable = [
    'name',
    ['slug', 'index' => true]
];
```

Once an attribute is indexed, you may use the `transWhere` method to apply a basic query to the model.

```php
Post::transWhere('slug', 'hello-world')->first();
```

The `transWhere` method accepts a third argument to explicitly pass a locale value, otherwise it will be detected from the environment.

```php
Post::transWhere('slug', 'hello-world', 'en')->first();
```

## URL Translation

Pages in the CMS support translating the URL property. Assuming you have 3 languages set up:

- en: English
- fr: French
- ru: Russian

There is a page with the following content:

```ini
url = "/contact"

[viewBag]
localeUrl[ru] = "/контакт"
==
<p>Page content</p>
```

The word "Contact" in French is the same so a translated URL is not given, or needed. If the page has no URL override specified, then the default URL will be used. Pages will not be duplicated for a given language.

- /fr/contact - Page in French
- /en/contact - Page in English
- /ru/контакт - Page in Russian
- /ru/contact - 404

### Translating URLs in Twig

The `localeUrl` method will replace the route prefix on a URL from one locale to another. For example, converting the current request URL from `en` to `de`.

```twig
{{ this.request.url|localeUrl('de') }}
```

The `localePage` will return a translated URL for a CMS page. It takes a locale (first argument) and page parameters (second argument).

```twig
{{ 'blog/post'|localePage('de', { slug: 'foobar' }) }}
```

## URL Parameter Translation

It's possible to translate URL parameters by listening to the `cms.sitePicker.overrideParams` event, which is fired when discovering language URLs.

```php
Event::listen('cms.sitePicker.overrideParams', function($page, $params, $oldSite, $newSite) {
    if ($page->baseFileName == 'your-page-filename') {
        return MyModel::translateParams($params, $oldSite->hard_locale, $newSite->hard_locale);
    }
});
```

In `MyModel`, one possible implementation might look like this:

```php
public static function translateParams($params, $oldLocale, $newLocale)
{
    $newParams = $params;
    foreach ($params as $paramName => $paramValue) {
        $records = self::transWhere($paramName, $paramValue, $oldLocale)->first();
        if ($records) {
            $records->translateContext($newLocale);
            $newParams[$paramName] = $records->$paramName;
        }
    }
    return $newParams;
}
```

## Query String Translation

It's possible to translate query string parameters by listening to the `cms.sitePicker.overrideQuery` event, which is fired when switching languages.

```php
Event::listen('cms.sitePicker.overrideQuery', function($page, $params, $oldSite, $newSite) {
    if ($page->baseFileName == 'your-page-filename') {
        return MyModel::translateParams($params, $oldSite->hard_locale, $newSite->hard_locale);
    }
});
```

For a possible implementation of the `MyModel::translateParams` method look at the example under `URL parameter translation` from above.

## Extend Theme Scan

```php
Event::listen('rainlab.translate.themeScanner.afterScan', function (ThemeScanner $scanner) {
    // ...
});
```

## Settings Model Translation

It's possible to translate your settings model like any other model. To retrieve translated values use:

```php
Settings::instance()->getAttributeTranslated('your_attribute_name');
```

## Conditionally Extending Plugins

#### Models

It is possible to conditionally extend a plugin's models to support translation by placing an `@` symbol before the behavior definition. This is a soft implement will only use `TranslatableModel` if the Translate plugin is installed, otherwise it will not cause any errors.

```php
/**
 * Post Model for the blog
 */
class Post extends Model
{
    // [...]

    /**
     * @var array implement the TranslatableModel behavior softly.
     */
    public $implement = ['@'.\RainLab\Translate\Behaviors\TranslatableModel::class];

    /**
     * @var array translatable attributes, if available.
     */
    public $translatable = ['title'];

    // [...]
}
```

The back-end forms will automatically detect the presence of translatable fields and replace their controls for multilingual equivalents.

# User Interface

#### Switching Locales

Users can switch between locales by clicking on the site selection menu in the backend panel. This will add a `_site_id` query value to the URL, allowing for multiple browser tabs to be used.
