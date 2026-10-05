<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Http\Middleware\NegotiateRootLocale;
use Tipi\Localization\Http\Middleware\RedirectDefaultLocale;
use Tipi\Localization\Http\Middleware\RememberLocale;
use Tipi\Localization\Http\Middleware\SetLocale;
use Tipi\Localization\Routing\LocalizedRouteRegistrar;
use Tipi\Localization\Support\LocaleCode;

function registerLocalizedAboutRoute(): Router
{
    $router = app('router');

    resolve(LocalizedRouteRegistrar::class)->routes(function (Router $router) {
        $router->get('/about', fn () => 'About')
            ->name('about');
    });

    $router->getRoutes()->refreshNameLookups();

    return $router;
}

it('registers default and localized routes when the default locale is hidden', function () {
    config()->set('localization.hide_default_locale', true);

    $router = registerLocalizedAboutRoute();

    expect($router->has('__localized.default.about'))->toBeTrue()
        ->and($router->has('__localized.locale.about'))->toBeTrue();

    expect(
        $router->getRoutes()
            ->getByName('__localized.default.about')
            ->uri()
    )->toBe('about');

    expect(
        $router->getRoutes()
            ->getByName('__localized.locale.about')
            ->uri()
    )->toBe('{locale}/about');
});

it('only registers localized routes when the default locale is visible', function () {
    $this->app->forgetInstance(LocalizationConfig::class);

    config()->set('localization.hide_default_locale', false);

    $router = registerLocalizedAboutRoute();

    expect($router->has('__localized.default.about'))->toBeFalse()
        ->and($router->has('__localized.locale.about'))->toBeTrue();

    expect(
        $router->getRoutes()
            ->getByName('__localized.locale.about')
            ->uri()
    )->toBe('{locale}/about');
});

it('applies middleware to default routes', function () {
    config()->set('localization.hide_default_locale', true);

    $router = registerLocalizedAboutRoute();

    $middleware = $router->getRoutes()
        ->getByName('__localized.default.about')
        ->gatherMiddleware();

    expect($middleware)
        ->toContain(SetLocale::class)
        ->toContain(NegotiateRootLocale::class)
        ->toContain(RememberLocale::class);
});

it('applies middleware to localized routes', function () {
    $router = registerLocalizedAboutRoute();

    $middleware = $router->getRoutes()
        ->getByName('__localized.locale.about')
        ->gatherMiddleware();

    expect($middleware)
        ->toContain(SetLocale::class)
        ->toContain(RedirectDefaultLocale::class)
        ->toContain(RememberLocale::class);
});

it('constrains the locale route parameter', function () {
    $router = registerLocalizedAboutRoute();

    $route = $router->getRoutes()
        ->getByName('__localized.locale.about');

    expect($route->wheres)
        ->toHaveKey('locale')
        ->and($route->wheres['locale'])
        ->toBe(LocaleCode::ROUTE_PATTERN);
});
