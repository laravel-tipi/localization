# Upgrade Guide

## From pre-V1 (0.x / dev-main) to 1.0

1.0 rewrites the runtime around locale contexts and fixes the routing, negotiation and persistence issues found in the pre-V1 audit. Expect to touch configuration, middleware and a few method calls.

### 1. Republish the config

Publish the new config and port your values: `php artisan vendor:publish --tag=localization-config --force`, after backing up the old file.

| Before | After |
|---|---|
| `negotiated_root_route_name` | `root_route` |
| `cookie.name` / `cookie.minutes` | `preference.cookie` / `preference.minutes`; the cookie is now named `{cookie}_{context}`, e.g. `locale_web` |
| `session.key` | removed (preferences are cookie-only) |
| `locale_model` | `models.locale` |
| `locale_policy` | `policies.locale` |
| `locales_table` | removed; set `$table` on a custom model |
| — | `contexts_driver`, `default_context`, `contexts`, `switch_route`, `models.context`, `policies.context` |

Invalid values now throw `InvalidLocaleConfigurationException` instead of PHP `ValueError`/`TypeError`.

Existing visitors' `locale` cookies are ignored after the upgrade. Visitors fall back to `Accept-Language` or the default until they choose again.

### 2. Database

V1 ships a fresh initial schema and **no upgrade migration**. Pre-V1 schemas are not migrated automatically. Back up your data first; do not run `migrate:fresh` on a database you need.

Publish the V1 migrations:

```bash
php artisan vendor:publish --tag=localization-migrations
```

Then choose one option:

- **Reset (simplest).** Export your locales, drop the `locales` table and delete its old migration row from `migrations`. Delete the old published `create_locales_table` migration, run `php artisan migrate`, and re-create the locales (for example with `CreateLocale`).
- **Migrate by hand.** Keep your old `create_locales_table` migration (it has already run) and delete the newly published copy, then write an app migration that brings the existing table to the V1 shape:
  - add `position` (unsigned integer, default 0, indexed) and fill it in display order;
  - widen `code` to `string(16)`;
  - add the "at most one default" constraint: on SQLite and PostgreSQL a unique index on `is_default` where it is true, on MySQL and MariaDB a stored generated column `default_guard` (`CASE WHEN is_default = 1 THEN 1 ELSE NULL END`) with a unique index. Copy the statements from the published `create_locales_table` migration.

  Then run `php artisan migrate`, which also creates the context tables.

`create_locale_contexts_tables` is new. Its tables are only needed when `contexts_driver` is `database`, but they are harmless otherwise.

When both `locales_driver` and `contexts_driver` are `database`, the locale and context models must use the same connection. The management actions throw `InvalidLocaleOperationException` otherwise.

MariaDB is now supported. Previously the migration threw "Unsupported database driver [mariadb]".

### 3. Middleware

| Before | After |
|---|---|
| `SetLocale`, `RedirectDefaultLocale`, `NegotiateRootLocale` | internal; applied by `Localization::routes()` |
| `RememberLocale` | removed: visits no longer write the preference; use `Localization::select()` or the switch endpoint |
| `SetRememberedLocale` | `UseLocaleContext::with('admin')` (alias `localization.context:admin`) |

### 4. Facade and helpers

| Before | After |
|---|---|
| `Localization::setCurrentLocale($l)` | `App::setLocale($code)` (or `Localization::select($l)` to also remember it) |
| `Localization::selectLocale($l)` | `Localization::select($l, ?$context)`; the cookie is now actually persisted |
| `Localization::supportedLocales()` | `Localization::context()->supported()` |
| `Localization::otherSupportedLocales()` | `Localization::switcher()` (or `->supported()->except(Localization::currentCode())`) |
| `Localization::localizedUrl($l)` / `localized_url()` | `Localization::currentUrl($l)` — returns `null` instead of throwing off localized routes |
| `Localization::redirectToRoute(...)` | `to_route($name, $params)` |
| `Localization::redirectToLocalizedRoute(...)` | `to_route($name, [...$params, 'locale' => Localization::negotiate()])` |
| `localized_route($name, $params, $locale)` | `route($name, [...$params, 'locale' => $locale])` (the helper was removed; Laravel's `route()` now resolves localized names) |
| `Localization::locales()->supported()` / `supportedCodes()` / `supportedLocale()` | `active()` / `activeCodes()` / `getActive()` (global) or `Localization::context()->supported()` / `codes()` / `get()` |
| — | `LocaleContext::isDefault($locale)` for the context default; `Locale::isDefault()` still means the global default |
| — | `hasRoute()`, `find()`, `isActive()`, `context()`, `contexts()`, `preference()`, `forgetPreference()`, `negotiate()`, `switchUrl()`, `switcher()`, `alternates()`, `routeIs()`, `currentRouteName()` |

`route()` and `Localization::route()` accept the locale as a code or a `Locale` object.

`Localization::current()` now always reflects `App::getLocale()`. Previously it cached the first value it saw, which ignored `App::setLocale()` and `withLocale()`. It also falls back to the context default instead of throwing when the application locale is not active.

### 5. Removed internal classes

`LocaleResolver`, `LocaleModelResolver`, `Enums\LocaleDriver` (now `Enums\StorageDriver`), `Actions\SetCurrentLocale` and `Actions\RememberCurrentLocale` were removed. Packages that used `LocaleResolver::current()` should call `Localization::current()`. Packages that used `LocaleRegistry::supported()` for *content* locales should use `Localization::locales()->active()`.

### 6. Routing behavior

- Localized routes must be named, uniquely, and not with a name also used by a regular route, whichever is registered first. Violations throw at registration or when the application boots (and so during `route:cache`).
- A static first segment shaped like a locale code (`/de`) is reserved; a locale with that code is rejected (`InvalidLocalizedRouteException`).
- `action([Controller::class, 'method'])` now resolves to the prefixed variant; generate localized URLs by name.
- The `{locale}` route parameter is removed before your controller runs. Read the locale with `Localization::current()` instead of `$request->route('locale')`.
- `/{default}/...` redirects are now `301` and only apply to `GET`/`HEAD`.
- Prefixed routes are registered before unprefixed ones, which fixes catch-all routes capturing `/ka`.
- With `hide_default_locale => false`, `/` now redirects to the root route in the default or negotiated locale.

### 7. Management actions

- Actions throw `LocaleNotFoundException` (not `ModelNotFoundException`) and `InvalidLocaleOperationException` (replacing `LocaleCannotBeActivatedException` etc.).
- `Activate`, `Deactivate` and `MakeDefault` now return the model.
- New locales are created **inactive** unless `active: true` is passed (the first locale is always active and the default).
- `CreateLocaleData` and `UpdateLocaleData` give `nativeAbbreviation`, `countryCode` and `textDirection` defaults, and validate their input.
- New actions: `DeleteLocale`, `ReorderLocales`, `CreateLocaleContext`, `UpdateLocaleContext`, `DeleteLocaleContext`.

### 8. Authorization

`LocalePolicy` now **denies** everything unless `manage-localization` is granted:

```php
Gate::define('manage-localization', fn (User $user) => $user->is_admin);
```

### 9. Exceptions

All exceptions implement `LocalizationException`. These were removed: `CurrentRouteCannotBeLocalizedException`, `DefaultLocaleNotConfiguredException`, `DefaultLocaleNotDefinedException` (now `DefaultLocaleMissingException`), `LocaleCannotBe*Exception`, `LocaleException`, `LocalesNotDefinedException`, `LocalizationConfigurationException`, `MultipleDefaultLocalesDefinedException` and `SupportedLocalesNotDefinedException`.
