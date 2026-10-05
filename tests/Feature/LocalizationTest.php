<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Tipi\Localization\LocaleRegistry;
use Tipi\Localization\Localization;
use Tipi\Localization\Routing\LocalizedRouteRegistrar;

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
        $router->get('/', fn () => 'Home')
            ->name('home');

        $router->get('/wines/{wine}', fn () => 'Wine')
            ->name('wines.show');
    });

    app('router')->getRoutes()->refreshNameLookups();
});

it('returns the locale registry', function () {
    $locales = resolve(Localization::class)->locales();

    expect($locales)
        ->toBeInstanceOf(LocaleRegistry::class)
        ->and($locales->all())
        ->toHaveCount(3)
        ->and($locales->all()->keys()->all())
        ->toBe(['en', 'ka', 'ru']);
});

it('returns the current locale', function () {
    app()->setLocale('ka');

    $locale = resolve(Localization::class)->current();

    expect($locale->code)->toBe('ka');
});

it('returns the current locale code', function () {
    app()->setLocale('ka');

    expect(
        resolve(Localization::class)->currentCode()
    )->toBe('ka');
});

it('generates a route for the current locale', function () {
    app()->setLocale('ka');

    $url = resolve(Localization::class)->route(
        name: 'wines.show',
        parameters: ['wine' => 10],
        absolute: false,
    );

    expect($url)->toBe('/ka/wines/10');
});

it('generates a route for an explicit locale', function () {
    $url = resolve(Localization::class)->route(
        name: 'wines.show',
        parameters: ['wine' => 10],
        locale: 'ka',
        absolute: false,
    );

    expect($url)->toBe('/ka/wines/10');
});

it('hides the default locale when generating a route', function () {
    $url = resolve(Localization::class)->route(
        name: 'wines.show',
        parameters: ['wine' => 10],
        locale: 'en',
        absolute: false,
    );

    expect($url)->toBe('/wines/10');
});

it('does not expose internal route names to consumers', function () {
    $url = resolve(Localization::class)->route(
        name: 'wines.show',
        parameters: ['wine' => 10],
        locale: 'ka',
        absolute: false,
    );

    expect($url)
        ->not->toContain('__localized');
});

it('redirects to a route using the negotiated browser locale', function () {
    request()->headers->set('Accept-Language', 'ka');

    $response = resolve(Localization::class)->redirectToRoute(
        name: 'wines.show',
        parameters: ['wine' => 10],
    );

    expect($response->getStatusCode())->toBe(302)
        ->and($response->getTargetUrl())
        ->toBe('http://localhost/ka/wines/10');
});

it('redirects to a route without a prefix when the negotiated locale is the default', function () {
    request()->headers->set('Accept-Language', 'en');

    $response = resolve(Localization::class)->redirectToRoute(
        name: 'wines.show',
        parameters: ['wine' => 10],
    );

    expect($response->getStatusCode())->toBe(302)
        ->and($response->getTargetUrl())
        ->toBe('http://localhost/wines/10');
});

it('supports a custom redirect status', function () {
    request()->headers->set('Accept-Language', 'ka');

    $response = resolve(Localization::class)->redirectToRoute(
        name: 'wines.show',
        parameters: ['wine' => 10],
        status: 301,
    );

    expect($response->getStatusCode())->toBe(301)
        ->and($response->getTargetUrl())
        ->toBe('http://localhost/ka/wines/10');
});
