# Laravel Tipi Localization

Localization and localized routing for Laravel 13.

The package provides a small locale runtime, localized route registration and URL generation, request locale negotiation, and optional database-backed locale management.

## Requirements

- PHP 8.5+
- Laravel 13+
- SQLite, MySQL, or PostgreSQL when using the database locale driver

## Installation

Install the package with Composer:

```bash
composer require laravel-tipi/localization
```

Laravel discovers the service provider automatically.

Publish the configuration when you want to customize the defaults:

```bash
php artisan vendor:publish --tag=localization-config
```

## Choosing a locale driver

The package supports two locale sources.

### Config driver

The config driver is the default and is suitable when the application's locales are fixed in source control.

```php
// config/localization.php

'locales_driver' => 'config',

'locales' => [
    [
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'country_code' => 'GB',
        'text_direction' => 'ltr',
    ],
    [
        'code' => 'ka',
        'name' => 'Georgian',
        'native_name' => 'ქართული',
        'country_code' => 'GE',
        'text_direction' => 'ltr',
    ],
],

'default_locale' => 'en',
```

Every locale defined by the config driver is active. The configured default locale must be one of the declared locale codes.

### Database driver

Use the database driver when locales need to be managed at runtime:

```php
'locales_driver' => 'database',
```

The package automatically loads its migration while the database driver is selected. Run:

```bash
php artisan migrate
```

The database stores the locale code, English and native names, optional country code, text direction, active state, and default state. The package enforces at most one default locale at the database level.

The database driver currently supports SQLite, MySQL, and PostgreSQL.

## Registering localized routes

Register routes through the package facade:

```php
use Illuminate\Support\Facades\Route;
use Tipi\Localization\Facades\Localization;

Localization::routes(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::get('/wines/{wine}', [WineController::class, 'show'])
        ->name('wines.show');
});
```

With `en` as the default locale and `hide_default_locale` enabled, the routes are exposed as:

```text
/wines/10
/ka/wines/10
```

The package owns the internal route names used to implement localized routing. Application code should continue using the names supplied to `Localization::routes()`.

## Generating localized URLs

The global helper is the most convenient option:

```php
localized_route('wines.show', ['wine' => $wine]);

localized_route(
    'wines.show',
    ['wine' => $wine],
    locale: 'ka',
);

localized_route(
    'wines.show',
    ['wine' => $wine],
    locale: 'ka',
    absolute: false,
);
```

When no locale is supplied, the current application locale is used.

The same API is available through the facade:

```php
Localization::route(
    name: 'wines.show',
    parameters: ['wine' => $wine],
    locale: 'ka',
);
```

## Redirects

`redirectToRoute()` redirects using the current application locale:

```php
return Localization::redirectToRoute(
    name: 'wines.show',
    parameters: ['wine' => $wine],
);
```

`redirectToLocalizedRoute()` is intended for entry points where the destination locale should be negotiated from the request:

```php
return Localization::redirectToLocalizedRoute(
    name: 'home',
);
```

Negotiation uses the first supported locale found in this order:

1. the remembered locale cookie;
2. the browser's `Accept-Language` header;
3. the default locale.

Both redirect methods accept an optional HTTP status code as their third argument.

## Accessing locales

Get the current locale:

```php
$locale = Localization::current();

$locale->code;
$locale->name;
$locale->nativeName;
$locale->countryCode;
$locale->textDirection;

$locale->isActive();
$locale->isInactive();
$locale->isDefault();
```

Get only the current code:

```php
$code = Localization::currentCode();
```

The locale registry is available through `Localization::locales()`:

```php
$locales = Localization::locales();

$locales->all();
$locales->supported();
$locales->supportedCodes();
$locales->get('ka');
$locales->supportedLocale('ka');
$locales->default();
$locales->defaultCode();
```

`all()` and `supported()` return collections keyed by locale code.

## Locale codes

The package intentionally supports a useful locale-code subset rather than every BCP 47 form. Supported examples include:

```text
en
en-US
en-001
zh-Hans
zh-Hans-CN
```

Country codes, when supplied separately, must be two uppercase letters. Text direction is `ltr` or `rtl`.

## Hiding the default locale

By default:

```php
'hide_default_locale' => true,
```

the default locale is omitted from URLs. If `en` is the default:

```text
/wines
/ka/wines
```

A request for a prefixed default-locale URL such as `/en/wines` is redirected to the canonical `/wines` URL.

Set the option to `false` to include every locale prefix:

```text
/en/wines
/ka/wines
```

## Root locale negotiation

Root negotiation is disabled by default:

```php
'negotiate_root_locale' => false,
'negotiated_root_route_name' => 'home',
```

When enabled, the package negotiates only the configured named root route. For example, a request to `/` with Georgian as the preferred supported language may redirect to `/ka`.

Other unprefixed routes are not automatically negotiated. This prevents ordinary default-locale URLs from unexpectedly switching languages.

## Remembering the locale

After a localized request, the package stores the resolved locale in a cookie:

```php
'cookie' => [
    'name' => 'locale',
    'minutes' => 60 * 24 * 365,
],
```

The cookie is subsequently considered before the browser language during locale negotiation.

## Managing database locales

For applications using the database driver, the package provides focused actions and DTOs.

Create a locale:

```php
use Tipi\Localization\Actions\CreateLocale;
use Tipi\Localization\Data\CreateLocaleData;
use Tipi\Support\Enums\TextDirection;

$locale = app(CreateLocale::class)->execute(
    new CreateLocaleData(
        code: 'fr',
        name: 'French',
        nativeName: 'Français',
        countryCode: 'FR',
        textDirection: TextDirection::Ltr,
    ),
);
```

The first created locale becomes the default automatically.

Update locale metadata:

```php
use Tipi\Localization\Actions\UpdateLocale;
use Tipi\Localization\Data\UpdateLocaleData;

$locale = app(UpdateLocale::class)->execute(
    code: 'fr',
    data: new UpdateLocaleData(
        name: 'French',
        nativeName: 'Français',
        countryCode: 'FR',
        textDirection: TextDirection::Ltr,
    ),
);
```

Activation and default-state changes are intentionally separate operations:

```php
use Tipi\Localization\Actions\ActivateLocale;
use Tipi\Localization\Actions\DeactivateLocale;
use Tipi\Localization\Actions\MakeLocaleDefault;

app(ActivateLocale::class)->execute('fr');
app(MakeLocaleDefault::class)->execute('fr');
app(DeactivateLocale::class)->execute('en');
```

A default locale cannot be deactivated, and an inactive locale cannot be made default.

## Testing and code style

Run the complete project checks with:

```bash
composer check
```

Or run them separately:

```bash
composer test
composer format:test
```

Apply Pint formatting with:

```bash
composer format
```

## License

Laravel Tipi Localization is open-source software licensed under the MIT license.
