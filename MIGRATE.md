# Migrating to Core Translatable

This guide is for plugin authors and site owners who want to switch from `RainLab.Translate` to the core translation features. Model attribute translation moved into the core `Translatable` trait in October CMS v4.2, and v4.4 completes the picture with native page, content, and mail template translation. **This migration is entirely opt-in** - the plugin continues to work as-is and both systems can coexist.

By v4.4, the core covers every area this plugin provides: model attributes (`Translatable` trait), page properties and URLs (`[translatable]` component), theme content files (locale directories), mail templates, and theme strings (scanned with `theme:scan` into `lang/*.json` files, editable in the backend Editor). The steps below migrate each area in turn.

## Why Translation Is Now a Core Feature

In the early designs of October CMS, translation was a secondary feature, something a site bolted on through a plugin when needed. As the platform has grown, we find most sites use translation from day one.

Multisite, introduced in v3.1, answered the question "_why have themes if I only ever use one?_". Core translation in v4.2 answers the next one: "_why is translation split across the core and a plugin?_".

There is also a concrete engineering reason. The plugin applies translation as a model _behavior_, and behaviors are an anti-pattern at scale. Each one constructs a sidecar object for every model instance, so a page that loads a thousand records also builds a thousand behavior objects to go with them. That is fine for something applied to a handful of models, but translation applies to most content models, so that object is paid for across the whole application.

Baking it into a core `Translatable` trait does the same work on the model itself, with no sidecar object, and on a single-locale site it short-circuits on a locale check and adds nothing at all. Sites upgrading can expect performance gains from the lower memory footprint.

## When to Switch

- You're building a new project and want to use the core API from the start
- You want zero plugin dependencies for translation support
- You want the per-row storage benefits: partial updates, direct queries, and no separate indexes table
- You want the performance benefit of the core `Translatable` trait over a per-model behavior

## When NOT to Switch

- Other plugins in your ecosystem depend on `RainLab.Translate` being present

## Prerequisites

- October CMS v4.2 or later for model attribute migration (Steps 1-6)
- October CMS v4.4 or later for theme, content, and mail template migration
- Back up your database before migrating

## Step 1: Update Model Declaration

For each model using the behavior, swap `$implement` for the trait.

```php
// Before (plugin behavior)
class Product extends Model
{
    public $implement = ['@RainLab.Translate.Behaviors.TranslatableModel'];

    public $translatable = ['name', 'description'];
}

// After (core trait)
class Product extends Model
{
    use \October\Rain\Database\Traits\Translatable;

    public $translatable = ['name', 'description'];
}
```

The `$translatable` property stays the same. The trait automatically uses the `system_translate_attributes` table via a container binding — no additional configuration needed.

## Step 2: Simplify Translatable Property

The core trait does not use per-attribute options like `index` or `fallback`. Remove these options from your `$translatable` array.

```php
// Before
public $translatable = [
    'name',
    ['slug', 'index' => true],
    ['title', 'fallback' => false],
];

// After
public $translatable = ['name', 'slug', 'title'];
```

**Why `index` is no longer needed:** The plugin stored all attributes as a single JSON blob, so searchable attributes had to be duplicated into a separate `rainlab_translate_indexes` table. The core trait uses per-row storage — every attribute is directly queryable by default.

**Why `fallback` is no longer needed:** The `fallback => false` option forced storage of values identical to the default locale, typically used as a workaround to make `hasTranslation()` reliable. The core trait provides `hasTranslations($locale)` for record-level checks and `getTranslatedLocales()` for listing translated locales, eliminating the need for this workaround.

## Step 3: Migrate the Data

Run the migration command to copy translation data from the plugin tables to the core table.

```bash
php artisan translate:import-attributes
```

Without any options, this migrates **all model types** from the plugin tables to the core table. It does **not** delete source data — your plugin tables are left intact.

This command:

1. Reads each row from `rainlab_translate_attributes`, decodes the JSON blob, and creates one row per attribute in `system_translate_attributes`
2. Reads `rainlab_translate_indexes` and imports any indexed values not already covered by the attributes table

**Options:**

```bash
# Skip confirmation prompt
php artisan translate:import-attributes --force

# Only migrate a specific model type
php artisan translate:import-attributes --model="Acme\Shop\Models\Product"

# Clean up source tables after successful migration
php artisan translate:import-attributes --cleanup
```

The command is idempotent — safe to run multiple times. It uses `upsert` so re-running overwrites with the latest source data rather than creating duplicates.

## Step 4: Update Method Calls

Search your codebase for the old method names and replace them. The table below covers every method.

Most of the renamed methods are retained in the core trait as deprecated aliases that delegate to the new API, so existing calls keep working during migration. Update them at your own pace; the aliases are marked `@deprecated` and may be removed in a future release.

### Changed Methods

| Old Method | New Method | Notes |
|---|---|---|
| `getAttributeTranslated($key, $locale)` | `getTranslation($key, $locale)` | Deprecated alias retained; adds optional `$useFallback` param |
| `setAttributeTranslated($key, $value, $locale)` | `setTranslation($key, $locale, $value)` | Deprecated alias retained; arg order changed |
| `getTranslateAttributes($locale)` | `getTranslations($key)` | Deprecated alias retained; note the semantic difference below |
| `translateContext($locale)` | `setLocale($locale)` | Deprecated alias retained; combined getter/setter |
| `translateContext()` | `getLocale()` | Deprecated alias retained; getter is now separate |
| `lang($locale)` | `setLocale($locale)` | Deprecated alias retained; chainable in both |
| `noFallbackLocale()` | `getTranslation($key, $locale, useFallback: false)` | Removed; fallback is now per-call |
| `withFallbackLocale()` | *(removed)* | Fallback is the default |
| `isTranslatable($key)` | `isTranslatableAttribute($key)` | Deprecated alias retained; renamed |

The retained `getTranslateAttributes($locale)` alias returns every translatable attribute for one locale (its original behavior), so it is not a straight rename of `getTranslations($key)`, which returns all locales for one attribute. See Step 5 for the distinction.

### Query Scopes

| Old Scope | New Scope | Notes |
|---|---|---|
| `transWhere($key, $value, $locale)` | *(unchanged)* | Retained with original signature, no changes needed |
| `transOrderBy($key, $direction, $locale)` | *(unchanged)* | Retained with original signature, no changes needed |

Both plugin scopes were kept in the core trait with their original signatures, so calls to them need no changes. They exist alongside the stricter core scopes rather than replacing them:

- `transWhere` matches either the base value or the translated value for the locale (defaulting to the active locale), and short-circuits to a plain `where` on the default locale. Use `whereTranslation($key, $locale, $value)` when you want to match only a stored translation row.
- `transOrderBy` sorts by the translated value, falling back to the base column value for untranslated records (defaulting to the active locale). Use `orderByTranslation($key, $locale, $direction)` when you want untranslated records to sort as `NULL` instead of by their default-locale value.

### Unchanged Methods

These work identically — no code changes needed:

| Method | Description |
|---|---|
| `hasTranslation($key, $locale)` | Check if translation exists |
| `shouldTranslate()` | Check if translation is active |
| `getTranslatableAttributes()` | Get translatable attribute names |
| `isTranslateDirty($attr, $locale)` | Check if translations changed |
| `getTranslateDirty($locale)` | Get changed translations |
| `getTranslatableOriginals($locale)` | Get original translation values |

### New Methods (No Old Equivalent)

| Method | Description |
|---|---|
| `setTranslations($key, $translations)` | Bulk set across locales |
| `hasTranslations($locale)` | Record-level "is this model translated?" |
| `getTranslatedLocales($key)` | List locales with translations |
| `forgetTranslation($key, $locale)` | Delete single translation |
| `forgetTranslations($key)` | Delete all locales for attribute |
| `forgetAllTranslations($locale)` | Delete all attributes for a locale |

## Step 5: Update Attribute Access Patterns

Implicit attribute access works identically in both systems:

```php
// These work the same in both — no changes needed
$product->name;           // Returns translated value for active locale
$product->name = 'Foo';   // Sets translated value for active locale
$product->save();
```

However, the `getTranslateAttributes` → `getTranslations` change is a semantic difference worth noting:

```php
// Old: returns all attributes for one locale
$data = $product->getTranslateAttributes('fr');
// Returns: ['name' => 'Produit', 'slug' => 'produit']

// New: returns all locales for one attribute
$data = $product->getTranslations('name');
// Returns: ['en' => 'Product', 'fr' => 'Produit', 'de' => 'Produkt']
```

If you need the old behavior (all attributes for one locale), load translations for that locale and read each attribute:

```php
$product->setLocale('fr');
$data = [];
foreach ($product->getTranslatableAttributes() as $key) {
    $data[$key] = $product->getTranslation($key, 'fr');
}
```

## Step 6: Verify

After completing the migration for a model:

1. **Read translations** — visit the backend form for a record and confirm translated values display correctly
2. **Write translations** — switch to a non-default locale, edit values, save, and confirm they persist
3. **Query scopes** — test any `whereTranslation` or `orderByTranslation` calls return expected results
4. **Fallback behavior** — confirm that untranslated attributes fall back to the default locale value

## Storage Differences

For reference, the two systems store data differently:

**RainLab.Translate** stores all attributes as a single JSON blob per model per locale:

```
| model_type | model_id | locale | attribute_data                    |
|------------|----------|--------|-----------------------------------|
| Product    | 42       | fr     | {"name":"Produit","slug":"produit"} |
```

**Core Translatable** stores one row per attribute:

```
| model_type | model_id | locale | attribute | value   |
|------------|----------|--------|-----------|---------|
| Product    | 42       | fr     | name      | Produit |
| Product    | 42       | fr     | slug      | produit |
```

This enables partial updates (change one attribute without rewriting the blob) and direct queries without a separate indexes table.

## Migrating Theme Files (October CMS v4.4+)

October CMS v4.4 translates CMS page properties (URL, title, description, meta fields) natively using the `[translatable]` component section, replacing the `[viewBag]` locale keys written by this plugin. When the core feature is detected, the plugin automatically stops attaching its page translation behaviors and the editor Translate popup, and the core takes over. Existing `[viewBag]` keys keep working as a read fallback, so migration is optional but recommended.

Run the import command to rewrite the page files in the active theme:

```bash
php artisan translate:import-theme
```

Use `--theme=` to target a specific theme and `--force` to skip confirmation prompts. The command converts keys as follows, keeping any values already present in a `[translatable]` section:

```ini
# Before
[viewBag]
localeUrl[fr] = "/contactez"
localeTitle[fr] = "Contactez"

# After
[translatable]
locales[fr][url] = "/contactez"
locales[fr][title] = "Contactez"
```

### Content Files

October CMS v4.4 also resolves translated content blocks from locale directories (`content/fr/welcome.htm`), replacing the file suffix convention used by this plugin (`welcome.fr.htm`). The same `translate:import-theme` command moves suffixed content files into their locale directory, preserving nested paths:

```
# Before
content/welcome.fr.htm
content/blog/intro.fr.htm

# After
content/fr/welcome.htm
content/fr/blog/intro.htm
```

Files whose target already exists are skipped and reported. The plugin continues to serve suffixed files with precedence over the core resolution, so un-migrated themes keep working and this migration can be run at any time.

### Theme Strings

October CMS v4.4 scans theme templates for translatable strings natively, replacing the plugin's **Settings → Translate messages → Scan for messages** workflow and its `translate:scan` command. The core scans layouts, pages, and partials for the same `|_`, `|__`, `__()`, `trans()`, and `trans_choice()` filters and functions, and adds any missing keys to the theme language file.

Run the scan against a theme:

```bash
php artisan theme:scan {theme}
```

Pass `--locale=` to target a specific locale file (defaults to the primary site locale) and `--dry-run` to list found strings without writing anything. Found keys are added to `lang/{locale}.json` in the theme with empty values, ready to be filled in.

Unlike the plugin, which stored scanned strings in the `rainlab_translate_message_data` database table, the core writes them to theme `lang/{locale}.json` files. If you need those strings kept in the database rather than on the filesystem, that is now covered by the database templates feature (`database_templates` in `config/cms.php`).

The language files can be edited directly in the backend **Editor**: open a `lang/{locale}.json` file to translate each key, the same way pages and partials are edited. There is no separate **Translate messages** settings area to visit.

### Mail Templates

October CMS v4.4 translates mail templates natively: database templates store translated attributes through the core `Translatable` trait, and registered view templates resolve from locale directories (`views/mail/fr/welcome.htm`), replacing the `{code}-{locale}` suffix convention used by this plugin.

This feature is disabled by default and must be enabled through the `backend_mail_template` multisite feature in `config/multisite.php`:

```php
'features' => [
    'backend_mail_template' => true,
],
```

Run the mail import command to migrate suffixed database templates:

```bash
php artisan translate:import-mail
```

The command copies the subject, HTML content and text content from each `welcome-fr` style record onto the base `welcome` template as translated attributes, keeping any translations already stored, then removes the suffix record so the core resolution takes over. If the base template does not exist yet, it is created first. Registered view templates using a suffix code live in plugin code and are reported instead; move the view file into a locale directory (`welcome-fr` becomes `fr/welcome`).
