<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Exceptions\LocaleNotFoundException;
use Tipi\Localization\Exceptions\UnsupportedLocaleException;
use Tipi\Localization\Routing\LocalizedRouteRegistrar;
use Tipi\Localization\Routing\LocalizedUrlGenerator;

beforeEach(function () {
    $this->artisan('migrate')->run();

    DB::table('locales')->insert([
        [
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'country_code' => 'GB',
            'text_direction' => 'ltr',
            'is_active' => true,
            'is_default' => true,
        ],
        [
            'code' => 'ka',
            'name' => 'Georgian',
            'native_name' => 'ქართული',
            'country_code' => 'GE',
            'text_direction' => 'ltr',
            'is_active' => true,
            'is_default' => false,
        ],
        [
            'code' => 'ru',
            'name' => 'Russian',
            'native_name' => 'Русский',
            'country_code' => 'RU',
            'text_direction' => 'ltr',
            'is_active' => false,
            'is_default' => false,
        ],
    ]);

    resolve(LocalizedRouteRegistrar::class)->routes(function (Router $router) {
        $router->get('/wines/{wine}', fn () => 'Wine')
            ->name('wines.show');
    });

    app('router')->getRoutes()->refreshNameLookups();
});

it('generates a URL without the default locale when it is hidden', function () {
    $url = resolve(LocalizedUrlGenerator::class)->route(
        name: 'wines.show',
        parameters: ['wine' => 10],
        locale: 'en',
        absolute: false,
    );

    expect($url)->toBe('/wines/10');
});

it('generates a URL with a non-default locale', function () {
    $url = resolve(LocalizedUrlGenerator::class)->route(
        name: 'wines.show',
        parameters: ['wine' => 10],
        locale: 'ka',
        absolute: false,
    );

    expect($url)->toBe('/ka/wines/10');
});

it('uses the current locale when no locale is provided', function () {
    app()->setLocale('ka');

    $url = resolve(LocalizedUrlGenerator::class)->route(
        name: 'wines.show',
        parameters: ['wine' => 10],
        absolute: false,
    );

    expect($url)->toBe('/ka/wines/10');
});

it('generates an absolute URL by default', function () {
    $url = resolve(LocalizedUrlGenerator::class)->route(
        name: 'wines.show',
        parameters: ['wine' => 10],
        locale: 'ka',
    );

    expect($url)->toBe('http://localhost/ka/wines/10');
});

it('preserves additional query parameters', function () {
    $url = resolve(LocalizedUrlGenerator::class)->route(
        name: 'wines.show',
        parameters: [
            'wine' => 10,
            'page' => 2,
        ],
        locale: 'ka',
        absolute: false,
    );

    expect($url)->toBe('/ka/wines/10?page=2');
});

it('includes the default locale when it is not hidden', function () {
    $this->app->forgetInstance(LocalizationConfig::class);

    config()->set('localization.hide_default_locale', false);

    resolve(LocalizedRouteRegistrar::class)->routes(function (Router $router) {
        $router->get('/products/{product}', fn () => 'Product')
            ->name('products.show');
    });

    app('router')->getRoutes()->refreshNameLookups();

    $url = resolve(LocalizedUrlGenerator::class)->route(
        name: 'products.show',
        parameters: ['product' => 10],
        locale: 'en',
        absolute: false,
    );

    expect($url)->toBe('/en/products/10');
});

it('rejects an unknown locale', function () {
    resolve(LocalizedUrlGenerator::class)->route(
        name: 'wines.show',
        parameters: ['wine' => 10],
        locale: 'de',
        absolute: false,
    );
})->throws(LocaleNotFoundException::class);

it('rejects an inactive locale', function () {
    resolve(LocalizedUrlGenerator::class)->route(
        name: 'wines.show',
        parameters: ['wine' => 10],
        locale: 'ru',
        absolute: false,
    );
})->throws(UnsupportedLocaleException::class);
