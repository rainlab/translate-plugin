# Upgrading from Translate v2 to v3

This guide can be used to help migrate from RainLab.Translate v2 to v3. It should be a straight forward process without any major disruptions.

## Upgrade Instructions

1. Run `php artisan plugin:install rainlab.translate` to request the latest version (you do not need to uninstall v2 first).

1. Run `php artisan translate:migratev2` to optimize the translation database tables.

1. Continue using this plugin as normal.

## Breaking Changes

### Translation Tables Only Support Integer Keys

The following tables now use integer keys:

| Table | Column |
| --- | --- |
| rainlab_translate_attributes | model_id |
| rainlab_translate_indexes | model_id |

Previously these tables allowed strings to be used as keys and this carried an 800% performance penalty that was noticeably slow at scale. This approach was intended to support both UUID (string) and ID (integer) primary keys. The support for UUIDs has now been dropped and related tables should introduce integer keys in addition to their UUID counterparts.

A migration command has been introduced to safely switch to integer keys whilst retaining the string key values. We strongly recommend taking a database backup before running this command.

```
php artisan translate:migratev2
```

This will perform the following steps for both tables:

1. Rename the string column to `str_model_id` so the existing values are not lost.
1. Introduce the new integer `model_id` column.
1. Copy values from the string column to the integer column, where possible.
1. Resize columns and add composite indexes for improved lookup performance.

If a string is found that cannot cast to integer (i.e. a UUID), a warning is displayed with a list of records that need to be attended to.

This optimization is not required for new installations.

## Soft Behaviors

Behaviors no longer require the `@` prefix for soft implementation. The following syntax is now equivalent:

```php
// Before (v2)
public $implement = ['@'.\RainLab\Translate\Behaviors\TranslatableModel::class];

// After (v3)
public $implement = [\RainLab\Translate\Behaviors\TranslatableModel::class];
```

The `@` prefix is still supported for backward compatibility.
