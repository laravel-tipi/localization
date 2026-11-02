<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Tipi\Localization\Exceptions\InvalidLocalizedRouteException;
use Tipi\Localization\Exceptions\UnsupportedLocaleException;
use Tipi\Localization\Facades\Localization;
use Tipi\Localization\Routing\LocalizedRoutes;
use Tipi\Localization\Tests\Fixtures\RoutesServiceProvider;
use Tipi\Localization\Tests\Fixtures\Wine;

function nativeRoutes(bool $cached): void
{
    Route::get('/plain/{id}', fn (string $id) => 'plain:'.$id)->name('plain');

    test()->localizedRoutes(function (Router $router) {
        $router->get('/', fn () => 'home:'.app()->getLocale())->name('home');
        $router->get('/wines/{wine}', fn (string $wine) => 'wine:'.app()->getLocale().':'.$wine)->name('wines.show');
        $router->get('/download/{file}', fn (string $file) => 'download:'.app()->getLocale().':'.$file)
            ->middleware('signed')
            ->name('download');
    }, web: true);

    if ($cached) {
        $router = app('router');
        $router->setCompiledRoutes($router->getRoutes()->compile());
    }
}

dataset('route modes', ['uncached' => false, 'cached' => true]);

it('generates localized URLs with route() in the current locale', function (bool $cached) {
    nativeRoutes($cached);

    expect(route('wines.show', ['wine' => 10], false))->toBe('/wines/10');

    app()->setLocale('ka');

    expect(route('wines.show', ['wine' => 10], false))->toBe('/ka/wines/10')
        ->and(route('wines.show', ['wine' => 10]))->toBe('http://localhost/ka/wines/10')
        ->and(route('home'))->toBe('http://localhost/ka');
})->with('route modes');

it('accepts an explicit locale parameter, as a code or a Locale', function (bool $cached) {
    nativeRoutes($cached);
    app()->setLocale('ka');

    expect(route('wines.show', ['wine' => 10, 'locale' => 'ru'], false))->toBe('/ru/wines/10')
        ->and(route('wines.show', ['wine' => 10, 'locale' => 'en'], false))->toBe('/wines/10')
        ->and(route('wines.show', ['wine' => 10, 'locale' => Localization::locales()->get('de')], false))->toBe('/de/wines/10');
})->with('route modes');

it('keeps positional, model and query parameters', function (bool $cached) {
    nativeRoutes($cached);
    $wine = (new Wine)->forceFill(['id' => 1, 'slug' => 'saperavi']);

    expect(route('wines.show', [10], false))->toBe('/wines/10')
        ->and(route('wines.show', [10, 'locale' => 'ka'], false))->toBe('/ka/wines/10')
        ->and(route('wines.show', $wine, false))->toBe('/wines/saperavi')
        ->and(route('wines.show', ['wine' => $wine, 'locale' => 'ka', 'page' => 2], false))->toBe('/ka/wines/saperavi?page=2');
})->with('route modes');

it('works with to_route() and redirect()->route()', function (bool $cached) {
    nativeRoutes($cached);
    app()->setLocale('ka');

    expect(to_route('wines.show', ['wine' => 10])->getTargetUrl())->toBe('http://localhost/ka/wines/10')
        ->and(redirect()->route('wines.show', ['wine' => 10, 'locale' => 'en'])->getTargetUrl())->toBe('http://localhost/wines/10');
})->with('route modes');

it('does not interfere with regular routes', function (bool $cached) {
    nativeRoutes($cached);
    app()->setLocale('ka');

    expect(route('plain', ['id' => 5], false))->toBe('/plain/5')
        ->and(route('plain', ['id' => 5, 'locale' => 'ka'], false))->toBe('/plain/5?locale=ka')
        ->and(fn () => route('missing'))->toThrow(RouteNotFoundException::class, 'Route [missing] not defined.');
})->with('route modes');

it('rejects unsupported or malformed locale parameters', function (bool $cached) {
    nativeRoutes($cached);

    expect(fn () => route('wines.show', ['wine' => 1, 'locale' => 'fr']))->toThrow(UnsupportedLocaleException::class)
        ->and(fn () => route('wines.show', ['wine' => 1, 'locale' => 5]))->toThrow(InvalidLocalizedRouteException::class, 'must be a locale code or a Locale');
})->with('route modes');

it('generates and validates signed URLs', function (bool $cached) {
    nativeRoutes($cached);

    $ka = URL::signedRoute('download', ['file' => 'report', 'locale' => 'ka']);
    $en = URL::temporarySignedRoute('download', now()->addMinute(), ['file' => 'report']);

    expect($ka)->toStartWith('http://localhost/ka/download/report?signature=')
        ->and($en)->toStartWith('http://localhost/download/report?expires=');

    $this->get($ka)->assertOk()->assertSeeText('download:ka:report');
    $this->get($en)->assertOk()->assertSeeText('download:en:report');
    $this->get(str_replace('/ka/', '/ru/', $ka))->assertForbidden();
    $this->get('/ka/download/report')->assertForbidden();
})->with('route modes');

it('reports logical names through Localization::hasRoute(), not Route::has()', function (bool $cached) {
    nativeRoutes($cached);

    expect(Localization::hasRoute('wines.show'))->toBeTrue()
        ->and(Localization::hasRoute('plain'))->toBeFalse()
        ->and(Route::has('wines.show'))->toBeFalse() // documented limitation
        ->and(Route::has('plain'))->toBeTrue();
})->with('route modes');

it('rejects a localized route whose name is already used by a regular route', function () {
    Route::get('/about', fn () => 'about')->name('about');

    $this->localizedRoutes(fn (Router $router) => $router->get('/about', fn () => 'about')->name('about'));
})->throws(InvalidLocalizedRouteException::class, 'same name as a regular route');

it('rejects duplicate localized route names', function () {
    $this->localizedRoutes(fn (Router $router) => $router->get('/a', fn () => 'a')->name('page'));
    $this->localizedRoutes(fn (Router $router) => $router->get('/b', fn () => 'b')->name('page'));
})->throws(InvalidLocalizedRouteException::class, 'registered more than once');

describe('name collisions registered after Localization::routes()', function () {
    afterEach(fn () => RoutesServiceProvider::$routes = null);

    it('rejects a later regular route with a localized name', function (string $uri) {
        $this->localizedRoutes(fn (Router $router) => $router->get('/about', fn () => 'about')->name('about'));
        Route::get($uri, fn () => 'plain')->name('about');

        resolve(LocalizedRoutes::class)->validate();
    })->with([
        'different URI' => '/about-us',
        'same URI, replacing the localized route' => '/about',
    ])->throws(InvalidLocalizedRouteException::class, 'Localized route [about] has the same name as a regular route');

    it('accepts distinct names', function () {
        nativeRoutes(cached: false);
        Route::get('/contact', fn () => 'contact')->name('contact');

        resolve(LocalizedRoutes::class)->validate();

        expect(route('wines.show', ['wine' => 7]))->toEndWith('/wines/7');
    });

    it('fails while the application boots, in whatever order the routes were registered', function (bool $regularFirst) {
        RoutesServiceProvider::$routes = function () use ($regularFirst) {
            $regular = fn () => Route::get('/about-us', fn () => 'plain')->name('about');
            $localized = fn () => Localization::routes(fn (Router $router) => $router->get('/about', fn () => 'about')->name('about'));

            $regularFirst ? [$regular(), $localized()] : [$localized(), $regular()];
        };

        expect(fn () => $this->createApplication())
            ->toThrow(InvalidLocalizedRouteException::class, 'same name as a regular route');
    })->with(['regular first' => true, 'regular last' => false]);

    it('boots normally when every name is unique', function () {
        RoutesServiceProvider::$routes = function () {
            Localization::routes(fn (Router $router) => $router->get('/about', fn () => 'about')->name('about'));
            Route::get('/contact', fn () => 'contact')->name('contact');
        };

        $app = $this->createApplication();

        expect($app['router']->getRoutes()->hasNamedRoute('contact'))->toBeTrue();
    });
});
