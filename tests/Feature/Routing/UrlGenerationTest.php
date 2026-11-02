<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Tipi\Localization\Exceptions\UnsupportedLocaleException;
use Tipi\Localization\Facades\Localization;

beforeEach(function () {
    $this->configureLocalization(['contexts' => ['admin' => ['locales' => ['en', 'ka']]]]);

    $this->localizedRoutes(function (Router $router) {
        $router->get('/', fn () => 'home')->name('home');
        $router->get('/wines/{wine}', fn () => json_encode([
            'current' => Localization::currentRouteName(),
            'is' => Localization::routeIs('wines.*'),
            'laravel' => request()->routeIs('wines.*'),
            'ka' => Localization::currentUrl('ka', absolute: false),
            'en' => Localization::currentUrl('en', absolute: false),
            'alternates' => Localization::alternates(),
        ]))->name('wines.show');
    });

    Route::get('/plain', fn () => json_encode([
        'url' => Localization::currentUrl('ka'),
        'is' => Localization::routeIs('*'),
        'name' => Localization::currentRouteName(),
        'alternates' => Localization::alternates(),
    ]))->name('plain');
});

it('generates localized URLs for logical route names', function () {
    expect(Localization::route('wines.show', ['wine' => 10], 'ka', false))->toBe('/ka/wines/10')
        ->and(Localization::route('wines.show', ['wine' => 10], 'en', false))->toBe('/wines/10')
        ->and(Localization::route('wines.show', ['wine' => 10, 'page' => 2], 'ka', false))->toBe('/ka/wines/10?page=2')
        ->and(Localization::route('wines.show', ['wine' => 10], Localization::locales()->get('ka')))->toBe('http://localhost/ka/wines/10')
        ->and(route('wines.show', ['wine' => 10, 'locale' => 'ka'], false))->toBe('/ka/wines/10');
});

it('rejects locales the routing context does not support', function () {
    $this->useDatabaseLocales(['en', 'ka', 'ru'], inactive: ['ru']);
    $this->localizedRoutes(fn (Router $router) => $router->get('/x', fn () => 'x')->name('x'));

    Localization::route('x', locale: 'ru');
})->throws(UnsupportedLocaleException::class, 'not supported by the [web] locale context');

it('uses the current locale, or the routing context default when the current locale is outside it', function () {
    app()->setLocale('ka');
    expect(Localization::route('home', absolute: false))->toBe('/ka');

    app()->setLocale('fr');
    expect(Localization::route('home', absolute: false))->toBe('/');
});

it('P13: exposes the logical route name and matches it', function () {
    $data = $this->get('/ka/wines/10')->json();

    expect($data['current'])->toBe('wines.show')
        ->and($data['is'])->toBeTrue()
        ->and($data['laravel'])->toBeFalse(); // documented: use Localization::routeIs()
});

it('generates the current page in another locale, keeping parameters and query string', function () {
    $data = $this->get('/ka/wines/10?page=2')->json();

    expect($data['ka'])->toBe('/ka/wines/10?page=2')
        ->and($data['en'])->toBe('/wines/10?page=2');
});

it('builds hreflang alternates for the routing context, with x-default', function () {
    $data = $this->get('/wines/10')->json();

    expect($data['alternates'])->toBe([
        'en' => 'http://localhost/wines/10',
        'ka' => 'http://localhost/ka/wines/10',
        'ru' => 'http://localhost/ru/wines/10',
        'de' => 'http://localhost/de/wines/10',
        'x-default' => 'http://localhost/wines/10',
    ]);
});

it('B10: never throws outside localized routes', function () {
    $this->get('/plain')->assertOk()->assertExactJson([
        'url' => null,
        'is' => false,
        'name' => null,
        'alternates' => [],
    ]);

    expect(Localization::currentUrl('ka'))->toBeNull(); // console / no route
});

it('B10: keeps 404 pages rendering when they use the switcher helpers', function () {
    $this->app['router']->fallback(fn () => response(json_encode(Localization::currentUrl('ka')), 404));

    $this->get('/does/not/exist/at/all')->assertNotFound()->assertSee('null');
});
