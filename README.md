# Translation plugin

Enables multi-lingual sites.

## Selecting a language

Different languages can be set up in the back-end area, with a single default language selected. This activates the use of the language on the front-end and in the back-end UI.

A visitor can select a language by prefixing the language code to the URL, this is then stored in the user's session as their chosen language. For example:

* `http://website/ru/` will display the site in Russian
* `http://website/fr/` will display the site in French
* `http://website/` will display the site in the default language or the user's chosen language.

## Language Picker Component

A visitor can select their chosen language using the `LocalePicker` component. This component will display a simple dropdown that changes the page language depending on the selection.

    title = "Home"
    url = "/"

    [localePicker]
    ==

    <h3>{{ 'Please select your language:'|_ }}</h3>
    {% component 'localePicker' %}

If translated, the text above will appear as whatever language is selected by the user. The dropdown is very basic and is intended to be restyled. A simpler example might be:

    [...]
    ==

    <p>
        Switch language to:
        <a href="#" data-request="onSwitchLocale" data-request-data="locale: 'en'">English</a>,
        <a href="#" data-request="onSwitchLocale" data-request-data="locale: 'ru'">Russian</a>
    </p>

## Message translation

Message or string translation is the conversion of adhoc strings used throughout the site. A message can be translated with parameters.

    {{ site.name|_ }}

    {{ 'Welcome to our website!'|_ }}

    {{ 'Hello :name!'|_({ name: 'Friend' }) }}

A message can also be translated for a choice usage.

    {{ 'There are no apples|There are :number applies!'|__(2, { number: 'two' }) }}

Themes can provide default values for these messages by defining a `translate` key in the `theme.yaml` file, located in the theme directory.

    name: My Theme
    # [...]

    translate:
        en:
            site.name: 'My Website'
            nav.home: 'Home'
            nav.video: 'Video'
            title.home: 'Welcome Home'
            title.video: 'Screencast Video'

You may also define the translations in a separate file, where the path is relative to the theme. The following definition will source the default messages from the file **config/lang.yaml** inside the theme.

    name: My Theme
    # [...]

    translate: config/lang.yaml

## Content translation

This plugin activates a feature in the CMS that allows content files to use language suffixes, for example:

* **welcome.htm** will contain the content in the default language.
* **welcome.ru.htm** will contain the content in Russian.
* **welcome.fr.htm** will contain the content in French.

## Model translation

Models can have their attributes translated by using the `RainLab.Translate.Behaviors.TranslatableModel` behavior and specifying which attributes to translate in the class.

    class User
    {
        public $implement = ['RainLab.Translate.Behaviors.TranslatableModel'];

        public $translatable = ['name'];
    }

The attribute will then contain the default language value and other language code values can be created by using the `translateContext()` method.

    $user = User::first();

    // Outputs the name in the default language
    echo $user->name;

    $user->translateContext('fr');

    // Sets the name in French
    $user->name = 'Giselle';

    // Outputs the name in French
    echo $user->name;

The `lang()` method is a shorthand version of `translateContext()` and is also chainable.

    // Outputs the name in French
    echo $user->lang('fr')->name;

This can be useful inside a Twig template.

    {{ user.lang('fr').name }}

There are ways to get and set attributes without changing the context.

    // Gets a single translated attribute for a language
    $user->getTranslateAttribute('name', 'fr');

    // Sets a single translated attribute for a language
    $user->setTranslateAttribute('name', 'Jean-Claude', 'fr');

## Conditionally extending plugins

#### Models

It is possible to conditionally extend a plugin's models to support translation by placing an `@` symbol before the behavior definition. This is a soft implement will only use `TranslatableModel` if the Translate plugin is installed, otherwise it will not cause any errors.

    /**
     * Blog Post Model
     */
    class Post extends Model
    {

        [...]

        /**
         * Softly implement the TranslatableModel behavior.
         */
        public $implement = ['@RainLab.Translate.Behaviors.TranslatableModel'];

        /**
         * @var array Attributes that support translation, if available.
         */
        public $translatable = ['title'];

        [...]

    }

The back-end forms will automatically detect the presence of translatable fields and replace their controls for multilingual equivalents.

#### Messages

Since the Twig filter will not be available all the time, we can pipe them to the native Laravel translation methods instead. This ensures translated messages will always work on the front end.

    /**
     * Register new Twig variables
     * @return array
     */
    public function registerMarkupTags()
    {
        // Check the translate plugin is installed
        if (!class_exists('RainLab\Translate\Behaviors\TranslatableModel'))
            return;

        return [
            'filters' => [
                '_' => ['Lang', 'get'],
                '__' => ['Lang', 'choice'],
            ]
        ];
    }
