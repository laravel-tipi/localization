# Laravel Tipi Localization

Locales, locale contexts and localized routing for Laravel 13.

- A global **locale registry**, read from config or from the database.
- **Locale contexts**: each interface (public site, admin panel, …) supports its own subset of locales, with its own default and its own remembered preference.
- **Localized routes** (`/wines`, `/ka/wines`) that work with Laravel's own `route()`, `to_route()` and signed URLs, with `route:cache`, and with locales added at runtime.
- **Negotiation** (remembered choice → `Accept-Language` → default), a safe **locale switch endpoint**, switcher data and `hreflang` alternates.
- **Management actions** for database-backed locales and contexts, guarded by deny-by-default policies.

## Requirements

- PHP 8.5+, Laravel 13+
- For the database drivers: SQLite, MySQL, MariaDB or PostgreSQL

## Installation

```bash
composer require laravel-tipi/localization
php artisan vendor:publish --tag=localization-config
```

Using a database driver (for locales or contexts)? Publish and run the migrations:

```bash
php artisan vendor:publish --tag=localization-migrations
php artisan migrate
```

The package does not run migrations automatically. Two migrations are published, and together they are the complete schema:

| Migration | Purpose |
|---|---|
| `create_locales_table` | `locales` table, with a database-level "at most one default" constraint (a partial unique index on SQLite and PostgreSQL, a unique generated column on MySQL and MariaDB) |
| `create_locale_contexts_tables` | `locale_contexts` and `locale_context_locales` (only needed with the `database` contexts driver) |

Supported databases: SQLite, MySQL, MariaDB and PostgreSQL.

## Concepts

| Term | Meaning | API |
|---|---|---|
| **Active locale** | A locale that exists globally and is enabled | `Localization::locales()->active()` |
| **Global default** | The registry's default locale; always active | `Localization::locales()->default()` |
| **Context** | A named subset of active locales for one interface | `Localization::context('admin')` |
| **Supported locale** | An active locale that a context offers | `$context->supported()` |
| **Context default** | A context's default; may differ from the global default | `$context->default()` |
| **Current locale** | The application locale right now (`App::getLocale()`) | `Localization::current()` |
| **Preference** | A locale the visitor explicitly chose, stored per context | `Localization::preference()` |

Contexts only narrow what an interface offers. They never change which locales exist. Content translation (the `translations` package) works with the global registry.

Application code works with immutable domain objects, whichever driver stores the data:

| Class | Role |
|---|---|
| `Tipi\Support\Locale` | One locale. `isActive()` and `isDefault()` describe it in the global list; `isDefault()` is the **global** default |
| `Tipi\Localization\LocaleContext` | A resolved context: its supported locales and its default (`$context->isDefault($locale)`) |
| `Tipi\Localization\Contexts\LocaleContextDefinition` | A context as stored (config or database), before resolution; for management UIs |
| `Tipi\Localization\Data\*Data` | Validated input for the management actions |

Eloquent models live only in `Tipi\Localization\Models` (`Models\Locale`, `Models\LocaleContext`). They are the persistence layer for the `database` drivers, and are what the management actions, policies and admin UIs work with. Everything else reads the domain objects (`Models\Locale::toLocale()` converts one).

## Locales

### Config driver (default)

```php
// config/localization.php
'locales_driver' => 'config',

'locales' => [
    ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'native_abbreviation' => 'EN', 'country_code' => 'GB'],
    ['code' => 'ka', 'name' => 'Georgian', 'native_name' => 'ქართული', 'native_abbreviation' => 'ქა', 'country_code' => 'GE'],
    ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'text_direction' => 'rtl'],
],

'default_locale' => 'en',
```

Every configured locale is active, and the array order is the display order. The configuration is validated on first use. Mistakes such as a typo in a driver name, an invalid code, a duplicate code or an unknown default throw `InvalidLocaleConfigurationException` with an explanation.

**Locale codes** follow a practical BCP 47 subset: `en`, `eng`, `en-US`, `en-001`, `zh-Hans`, `zh-Hans-CN` (at most 12 characters). `native_abbreviation` is up to 3 characters, `country_code` is two uppercase letters, and `text_direction` is `ltr` (the default) or `rtl`.

### Database driver

```php
'locales_driver' => 'database',
```

Locales live in the `locales` table and are managed with the actions described under "Managing locales". They are ordered by `position`, then `code`.

### Reading locales

```php
use Tipi\Localization\Facades\Localization;

$registry = Localization::locales();

$registry->all();          // Collection<string, Locale>, every locale, keyed by code, in order
$registry->active();       // active locales only
$registry->activeCodes();  // ['en', 'ka']
$registry->find('ka');     // ?Locale
$registry->get('ka');      // Locale, or throws LocaleNotFoundException
$registry->getActive('ka');// Locale, or throws LocaleNotFoundException / UnsupportedLocaleException
$registry->isActive('ka'); // bool
$registry->default();      // the global default Locale
$registry->defaultCode();  // 'en'

$locale = $registry->get('ka');
$locale->code;               // 'ka'
$locale->name;               // 'Georgian'
$locale->nativeName;         // 'ქართული'
$locale->nativeAbbreviation; // 'ქა'
$locale->countryCode;        // 'GE'
$locale->textDirection;      // TextDirection::Ltr
$locale->isActive();
$locale->isDefault();        // the GLOBAL default; for a context use $context->isDefault($locale)
```

## Contexts

```php
// config/localization.php
'contexts_driver' => 'config',
'default_context' => 'web',

'contexts' => [
    'web' => [
        'locales' => '*',            // every active locale
    ],
    'admin' => [
        'locales' => ['en', 'ka'],
        'default' => 'en',           // optional; omitted means "follow the global default"
    ],
],
```

With no `contexts` at all, the package provides a single `web` context that contains every active locale. That matches the behavior of an application without contexts.

**Invariants**, enforced when a context is resolved and before one is stored:

1. A locale list must contain the **global default** locale.
2. A context's own default must belong to the context.
3. Listed locales must exist in the global registry (a context cannot introduce locales).
4. Inactive locales are never offered, even when a context lists them.
5. A stored context default must be active.

A violation throws `InvalidLocaleContextException`, naming the context and the rule. The default context (`default_context`) always exists. It is used by localized routes, console commands and queued jobs.

```php
$admin = Localization::context('admin');

$admin->name;            // 'admin'
$admin->supported();     // Collection<string, Locale>, in global order
$admin->codes();         // ['en', 'ka']
$admin->supports('ru');  // false
$admin->find('ru');      // null
$admin->get('ru');       // throws UnsupportedLocaleException
$admin->default();       // context default Locale
$admin->defaultCode();   // 'en'
$admin->isDefault('en'); // true: the context default, which may differ from $locale->isDefault()

Localization::context();             // the current context
Localization::contexts()->names();   // ['web', 'admin']
Localization::contexts()->all();     // Collection<string, LocaleContext>
Localization::contexts()->has('shop');
```

### Selecting a context for a part of the application

Localized routes always use the default context. For other areas (admin panels, dashboards, APIs) use the middleware:

```php
use Tipi\Localization\Http\Middleware\UseLocaleContext;

Route::middleware(['web', UseLocaleContext::with('admin')])->group(function () {
    // ...
});

// or with the alias
Route::middleware(['web', 'localization.context:admin'])->group(/* ... */);
```

The middleware makes the context current and sets the application locale to the visitor's preference for that context. Failing that, it uses the best `Accept-Language` match within the context, and then the context default.

### Database-backed contexts

```php
'contexts_driver' => 'database',
```

The two drivers are independent: config-defined locales can be combined with database-managed contexts, and the reverse.

**Source of truth.** With the database driver, the `locale_contexts` tables own the context definitions. Contexts listed in `config('localization.contexts')`, plus the default context, are **declared** contexts:

- They always exist. Until a row is stored for them, their config definition is used. Reading never writes to the database.
- Their first `UpdateLocaleContext` stores a row, and from then on the stored definition is used.
- They cannot be deleted, because application code depends on them (for example a panel that uses `UseLocaleContext::with('admin')`).

Contexts created with `CreateLocaleContext` are ordinary rows and can be deleted.

```php
use Tipi\Localization\Actions\CreateLocaleContext;
use Tipi\Localization\Actions\DeleteLocaleContext;
use Tipi\Localization\Actions\UpdateLocaleContext;
use Tipi\Localization\Data\CreateLocaleContextData;
use Tipi\Localization\Data\UpdateLocaleContextData;

app(CreateLocaleContext::class)->execute(
    new CreateLocaleContextData(name: 'shop', locales: ['en', 'ka'], default: 'ka'),
);

app(UpdateLocaleContext::class)->execute(
    'admin',
    new UpdateLocaleContextData(locales: ['en', 'ka', 'ru'], default: 'en'),
);

app(UpdateLocaleContext::class)->execute('kiosk', new UpdateLocaleContextData(locales: null)); // "*"

app(DeleteLocaleContext::class)->execute('shop');
```

All context actions validate the invariants, run in a transaction, lock the global default row (so they cannot race a default-locale change) and return the `Models\LocaleContext` model (`$model->localeCodes()` returns `null` for `*`). The stored, unresolved definition is available as `Localization::contexts()->definition('admin')`, which has `locales`, `default`, `required` and `persisted`.

## Global default vs context defaults

- The **global default** (`default_locale`, or the `is_default` row) must be in every context.
- A **context default** is the language an interface falls back to. For example, the site may default to `ka` while the admin defaults to `en`.
- Localized URLs hide the **default context's default** (see below).

Changing the global default (`MakeLocaleDefault`) keeps the invariants intact:

- **Database contexts:** the new default is added to every context that lists locales, in the same transaction.
- **Config contexts:** the change is rejected when a context doesn't list the new default (add it to the config first).

A locale that is a context default cannot be deactivated or deleted until the context chooses another default.

## Localized routing

```php
use Illuminate\Support\Facades\Route;
use Tipi\Localization\Facades\Localization;

Localization::routes(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/wines/{wine}', [WineController::class, 'show'])->name('wines.show');
    Route::post('/contact', ContactController::class)->name('contact');
});
```

With `en` as the default context's default and `hide_default_locale => true`:

| URL | Result |
|---|---|
| `/wines/10` | English |
| `/ka/wines/10` | Georgian |
| `GET /en/wines/10?page=2` | `301` to `/wines/10?page=2` |
| `POST /en/contact` | handled in English (never redirected) |
| `/fr/wines/10` (unknown or inactive) | `404` |

With `hide_default_locale => false`, every locale is prefixed (`/en/wines`), unprefixed localized URLs return 404, and `/` redirects to the root route (`root_route`) in the default or negotiated locale.

Rules and caveats:

- **Every localized route must be named, and the name must be unique.** An unnamed route, a name used twice, or a name also used by a regular route throws `InvalidLocalizedRouteException`, in whatever order the routes are registered. The routes are checked when `Localization::routes()` registers them, and the whole route list is checked again once the application has booted (so regular routes from later route files or providers are included). `route:cache` boots the application, so a collision fails the cache build; a cached application is not checked again. Routes added at runtime, after boot, are only checked against routes that already exist when `Localization::routes()` runs.
- **The `{locale}` parameter is removed** from the route before your controller runs. Laravel passes scalar parameters by position, so `show(string $slug)` receives the slug, not `ka`. Use `Localization::current()` to read the locale.
- The closure is executed twice (once per variant), so avoid side effects inside it.
- **Route caching and runtime locales.** The `{locale}` segment matches any code-shaped value, and support is checked per request. Locales created or activated at runtime therefore work immediately, even with `php artisan route:cache`.
- **Catch-all routes.** Prefixed routes are matched first, so `/ka/about` is never captured by an unprefixed `/{page}` route. Static first segments that look like locale codes (such as `/faq` or `/map`) are excluded automatically. The remaining limitation: a *parameter value* that looks like a locale code, such as the slug `map` on `/{page}`, matches the prefixed home route and returns 404. Constrain such parameters, for example `->where('page', '[a-z0-9-]{4,}')`.
- **Static segments vs locale codes.** A static first segment that looks like a locale code (`/faq`, `/de`) is *reserved* for its page. A locale with the same code would make either the page or the locale unreachable, so the package refuses it rather than serving the wrong content:
  - with the config driver, `Localization::routes()` throws `InvalidLocalizedRouteException` at registration;
  - `CreateLocale` and `ActivateLocale` reject the code;
  - if the database is changed directly, localized requests throw the same exception until the route or the locale is renamed.

### URLs with Laravel's own helpers

Laravel's `route()` and everything built on it work with the logical names:

```php
route('wines.show', ['wine' => $wine]);                     // current locale: /wines/10 or /ka/wines/10
route('wines.show', ['wine' => $wine, 'locale' => 'ka']);   // explicit locale (code or Locale)
route('wines.show', $wine);                                 // positional and model parameters
route('wines.show', ['wine' => $wine, 'page' => 2], false); // query string, relative URL

to_route('wines.show', ['wine' => $wine]);
redirect()->route('wines.show', ['wine' => $wine, 'locale' => 'en']);

URL::signedRoute('download', ['file' => 'report', 'locale' => 'ka']);  // /ka/download/report?signature=...
URL::temporarySignedRoute('download', now()->addHour(), ['file' => 'report']);
```

This uses Laravel's `UrlGenerator::resolveMissingNamedRoutesUsing()` hook. Laravel asks the package only when no route has the exact name, so:

- regular routes are never affected, and a `locale` parameter on a regular route stays an ordinary query parameter;
- the internal route names stay unique, and `route:cache` works unchanged;
- signed URLs are signed and validated (`signed` middleware) on the localized URL.

Without a `locale` parameter, the current locale is used when the default context supports it, otherwise that context's default. A locale outside the routing context throws `UnsupportedLocaleException`.

`Localization::route($name, $parameters, $locale, $absolute)` is the same operation with a typed `$locale` argument.

**What does not see logical names:** these read Laravel's physical route names directly and have no extension hook. Use the package equivalents:

| Laravel | Use instead |
|---|---|
| `request()->routeIs('wines.*')`, `Route::is()` | `Localization::routeIs('wines.*')` |
| `Route::has('wines.show')` | `Localization::hasRoute('wines.show')` |
| `Route::currentRouteName()` | `Localization::currentRouteName()` |
| `action([WineController::class, 'show'], ...)` | `route('wines.show', ...)` (action URLs resolve to the prefixed variant and need a `locale` parameter) |

Other notes:

- Laravel stores a single missing-route resolver. If your application or another package calls `URL::resolveMissingNamedRoutesUsing()`, the last one registered wins.

### Current page and route name

```php
Localization::currentUrl('ka');      // this page in Georgian (params + query kept), or null off localized routes
Localization::currentRouteName();    // 'wines.show' or null
Localization::routeIs('wines.*');    // bool
Localization::hasRoute('wines.show');
```

### SEO alternates

```blade
@foreach (Localization::alternates() as $hreflang => $url)
    <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $url }}">
@endforeach
```

`alternates()` returns `hreflang => absolute URL` for every supported locale of the default context, plus `x-default`. It is empty on non-localized pages.

## Switching and remembering the locale

The package registers `GET /locale/{locale}` (named `localization.switch`, in the `web` middleware group). It records the choice for the context and redirects to a **same-site path only**: absolute URLs, `//host`, backslashes and control characters fall back to `/`.

```blade
<nav>
    @foreach (Localization::switcher() as $link)
        <a href="{{ $link->url }}" @class(['active' => $link->current]) hreflang="{{ $link->locale->code }}">
            {{ $link->locale->nativeName }}
        </a>
    @endforeach
</nav>
```

```php
Localization::switchUrl('ka');            // /locale/ka?redirect=/ka/wines/10
Localization::switchUrl('ka', 'admin');   // /locale/ka?redirect=/admin/settings&context=admin
```

On a localized page, the switch URL returns to the same page in the chosen locale. On other pages it returns to the current path.

From your own code (a Livewire action, a controller, an API endpoint):

```php
Localization::select('ka');            // current context: remembered + applied immediately
Localization::select('ka', 'admin');   // another context: remembered only
Localization::preference();            // ?Locale, the remembered choice (if still supported)
Localization::forgetPreference('admin');
```

- Preferences are stored in one cookie per context, named `"{preference.cookie}_{context}"` (`locale_web`, `locale_admin`). The cookie goes through `EncryptCookies` like any other cookie and is written only when its value changes. Writing it requires the `web` group (`AddQueuedCookiesToResponse`).
- **Only explicit selection writes a preference.** Visiting `/ka/...` does not change it, so a shared link never overrides a visitor's choice.
- A public-site choice never affects the admin, and the reverse also holds.

### Root negotiation

```php
'negotiate_root_locale' => true,
'root_route' => 'home',
```

A request for the unprefixed root route is redirected according to the preference for the default context, then `Accept-Language`, and is served as-is when the result is the default. Only that route is negotiated. Responses carry `Vary: Accept-Language, Cookie`. Because the switch endpoint records explicit choices, a visitor who picks the default language is not redirected away again.

`Accept-Language` matching is case-insensitive and accepts `_` or `-`. It tries the exact tag, then the tag with trailing subtags removed (`zh-Hant-TW` → `zh-Hant` → `zh`), then any supported code with the same language (`en` → `en-US`). It skips `q=0` and returns the context default when nothing matches.

```php
Localization::negotiate();                 // default/current context, current request
Localization::negotiate('admin', $request);
```

## Current locale

```php
Localization::current();      // Locale
Localization::currentCode();  // 'ka'
```

`current()` reflects `App::getLocale()`, so `App::setLocale()` and `withLocale()` (used by mailables and notifications) work as expected. When the application locale isn't an active locale (for example Laravel's `app.locale` in a console command), it falls back to the current context's default instead of throwing. To change the locale for a request, use `App::setLocale()`; to remember a choice, use `Localization::select()`.

The registry, contexts and current context reset for every request, queued job and Octane request (scoped bindings).

## Managing locales (database driver)

```php
use Tipi\Localization\Actions\{ActivateLocale, CreateLocale, DeactivateLocale, DeleteLocale, MakeLocaleDefault, ReorderLocales, UpdateLocale};
use Tipi\Localization\Data\{CreateLocaleData, UpdateLocaleData};
use Tipi\Support\Enums\TextDirection;

$locale = app(CreateLocale::class)->execute(new CreateLocaleData(
    code: 'fr',
    name: 'French',
    nativeName: 'Français',
    nativeAbbreviation: 'FR',   // optional
    countryCode: 'FR',          // optional
    textDirection: TextDirection::Ltr,
    active: false,              // default; the first locale is always active and the default
));

app(UpdateLocale::class)->execute('fr', new UpdateLocaleData(name: 'French', nativeName: 'Français'));
app(ActivateLocale::class)->execute('fr');
app(MakeLocaleDefault::class)->execute('fr');
app(DeactivateLocale::class)->execute('en');
app(DeleteLocale::class)->execute('en');           // inactive, non-default locales only
app(ReorderLocales::class)->execute(['fr', 'en']); // display order
```

- The DTOs validate their input (code format, lengths, country code) and throw `InvalidLocaleDataException`.
- Every action except `DeleteLocale` and `ReorderLocales` returns the `Models\Locale`.
- Actions run in one transaction, lock the affected rows, and refresh the in-memory registry. A failed action leaves the database unchanged.
- When both locales and contexts are stored in the database, both models must use the **same connection**, so changes that touch both (such as `MakeLocaleDefault`) stay atomic. Otherwise the actions throw `InvalidLocaleOperationException` before writing anything. Reading works with any connections.
- Rejected operations throw `InvalidLocaleOperationException` with the reason, and unknown codes throw `LocaleNotFoundException`.
- Using them with the config driver throws `InvalidLocaleOperationException`.

For forms, `LocaleRules::create()`, `LocaleRules::update()`, `LocaleRules::attributes()`, `LocaleContextRules::create()` and `LocaleContextRules::update()` return matching Laravel validation rules. Context invariants are left to the actions.

### Custom models

```php
'models' => [
    'locale' => App\Models\Language::class,       // must extend Tipi\Localization\Models\Locale
    'context' => App\Models\LanguageContext::class, // must extend Tipi\Localization\Models\LocaleContext
],
```

Set `$table` and `$connection` on the model. Migrations, repositories, actions and validation rules all use them. With both database drivers, give both models the same `$connection` (see above). Override `membershipTable()` on the context model to rename the membership table.

## Authorization

The package registers `LocalePolicy` and `LocaleContextPolicy`. Both **deny everything** unless the user is granted the `manage-localization` ability:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('manage-localization', fn (User $user) => $user->is_admin);
```

`LocalePolicy` abilities are `viewAny`, `view`, `create`, `update`, `activate`, `deactivate`, `makeDefault`, `reorder` and `delete`. `LocaleContextPolicy` abilities are `viewAny`, `view`, `create`, `update` and `delete`. `Gate::before()` callbacks apply as usual. Replace a policy with `config('localization.policies.locale')` or `config('localization.policies.context')`.

Policies only answer "may this user do this?". Whether an operation is possible (for example deactivating the default) is decided by the actions. The model helpers `canBeActivated()`, `canBeDeactivated()`, `canBeMadeDefault()` and `canBeDeleted()` check a locale's own state for showing or hiding buttons.

## Building a management UI (e.g. Filament)

The future `filament-localization` resources should be thin layers over this API:

- **Authorization:** use the policies (resource visibility through `viewAny`; actions through `activate`, `makeDefault`, …).
- **Locales table:** `Models\Locale::query()->ordered()`. Reordering calls `ReorderLocales`; don't write `position` directly.
- **Forms:** `LocaleRules` / `LocaleContextRules` for field errors; build the DTOs with `fromArray($data)`.
- **Context form:** a "*" toggle maps to `locales: null`. Locale options come from `Localization::locales()->all()`, inactive locales included, and the global default should be pre-selected and locked. The default select is limited to the chosen locales, or empty to follow the global default. Prefill from `Localization::contexts()->definition($name)`, which covers declared contexts that have no row yet.
- **Delete buttons:** hide them for `definition($name)->required`.
- **Error handling:** call the actions and show `InvalidLocaleOperationException`, `InvalidLocaleContextException` and `InvalidLocaleDataException` messages as notifications. They are written for end users.
- **Panels:** use `UseLocaleContext::with('admin')` (or one context per panel) and `Localization::select($code)` in the switcher, with `Localization::switcher()` for its entries.

## Exceptions

All of them implement `Tipi\Localization\Exceptions\LocalizationException`.

| Exception | When |
|---|---|
| `InvalidLocaleConfigurationException` | Invalid `config/localization.php`, or the switch route is used while disabled |
| `LocaleNotFoundException` | A code doesn't exist in the registry (`$e->locale`) |
| `UnsupportedLocaleException` | A locale is inactive, or not supported by a context (`$e->locale`, `$e->context`) |
| `DefaultLocaleMissingException` | No active default locale (for example an empty `locales` table) |
| `LocaleContextNotFoundException` | Unknown context name |
| `InvalidLocaleContextException` | A context definition breaks an invariant |
| `InvalidLocaleDataException` | A DTO received malformed data |
| `InvalidLocaleOperationException` | A management action was rejected, or a driver mismatch |
| `InvalidLocalizedRouteException` | Unnamed, duplicate or colliding localized route names; a locale code equal to a reserved static segment; a malformed `locale` route parameter |

Unknown or unsupported locales in URLs and on the switch endpoint return 404 rather than throwing.

## Testing and code style

```bash
composer check        # pint --test + pest
composer test
composer format
```

The suite runs on in-memory SQLite by default. To run it against a real server:

```bash
LOCALIZATION_TEST_DB=mysql LOCALIZATION_TEST_DB_PORT=3306 LOCALIZATION_TEST_DB_PASSWORD=secret vendor/bin/pest
LOCALIZATION_TEST_DB=mariadb ...
LOCALIZATION_TEST_DB=pgsql LOCALIZATION_TEST_DB_PORT=5432 ...
```

Other variables: `LOCALIZATION_TEST_DB_HOST`, `LOCALIZATION_TEST_DB_DATABASE` (default `tipi`) and `LOCALIZATION_TEST_DB_USERNAME`. The database is wiped before each test. CI runs the suite on SQLite, MySQL 8.4, MariaDB 11 and PostgreSQL 16.

## License

MIT.
