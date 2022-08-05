# Upgrading from v1 to v2

This guide can be used to help migrate from v1 of this plugin to v2. It should be straight forward to migrate from v1 to v2 without any major disruptions.

Since October CMS v3.1, a multisite feature is introduced that supercedes many functions provided by this plugin. The existing data structures are still used in v2 of this plugin. All translations are carried across and no "retranslation" or modifying existing plugins should be needed.

## Upgrade Instructions

Navigate to **System → Sites** and create a site for each locale used by the website. Continue using this plugin as normal.

## Key Differences

- The "Languages" settings area is replaced by the "Sites" settings area. This is where available sites are defined, including the available languages.

- The per-field language picker is replaced by a per-site language picker. The translator selects the active language globally and can perform comparisons using multiple browser tabs. It is possible to retain per-field language pickers as a separate plugin (out of scope).

- Storing the locale in the session is no longer used and must be determined by the site definition, as a hostname or route prefix. Provisions are made for session-based site selection to exist as a separate plugin (out of scope).

## Key Similarities

- Models are still translated using the `RainLab\Translate\Behaviors\TranslatableModel`.

- Theme translation is still managed by this plugin.

## Breaking Changes

### Middleware class is replaced

If using PHP routes to determine locale, the `RainLab\Translate\Classes\LocaleMiddleware` class is replaced by the `System\Middleware\ActiveSite` middleware class.
