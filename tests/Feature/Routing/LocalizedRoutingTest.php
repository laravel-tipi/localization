<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Tipi\Localization\Actions\ActivateLocale;
use Tipi\Localization\Actions\CreateLocale;
use Tipi\Localization\Data\CreateLocaleData;
use Tipi\Localization\Exceptions\InvalidLocalizedRouteException;
use Tipi\Localization\Facades\Localization;

function registerSiteRoutes(): void
{
    test()->localizedRoutes(function (Router $router) {
        $router->get('/', fn () => 'home:'.app()->getLocale())->name('home');
        $router->get('/faq', fn () => 'faq:'.app()->getLocale())->name('faq');
        $router->get('/wines/{wine}', fn (string $wine) => 'wine:'.app()->getLocale().':'.$wine)->name('wines.show');
        $router->post('/contact', fn () => 'contact:'.app()->getLocale())->name('contact');
        $router->get('/{page}', fn (string $page) => 'page:'.app()->getLocale().':'.$page)->name('page');
        $router->get('/{category}/{slug}', fn (string $category, string $slug) => 'post:'.app()->getLocale().":{$category}:{$slug}")->name('post');
    });
}

it('serves the default locale unprefixed and other locales prefixed', function () {
    registerSiteRoutes();

    $this->get('/')->assertSeeText('home:en');
    $this->get('/ka')->assertSeeText('home:ka');
    $this->get('/wines/10')->assertSeeText('wine:en:10');
    $this->get('/ka/wines/10')->assertSeeText('wine:ka:10');
});

it('passes route parameters, not the locale, to positional route arguments', function () {
    registerSiteRoutes();

    $this->get('/ka/wines/10')->assertSeeText('wine:ka:10');
    $this->get('/ka/about-us')->assertSeeText('page:ka:about-us');
});

it('B4: does not let unprefixed catch-all routes capture locale prefixes', function () {
    registerSiteRoutes();

    $this->get('/ka')->assertSeeText('home:ka');
    $this->get('/ka/about-us')->assertSeeText('page:ka:about-us');
    $this->get('/ka/news/hello')->assertSeeText('post:ka:news:hello');
    $this->get('/about-us')->assertSeeText('page:en:about-us');
    $this->get('/news/hello')->assertSeeText('post:en:news:hello');
});

it('keeps static locale-shaped first segments on their unprefixed route', function () {
    registerSiteRoutes();

    $this->get('/faq')->assertSeeText('faq:en');
    $this->get('/ka/faq')->assertSeeText('faq:ka');
});

it('returns 404 for unknown and inactive locale prefixes', function () {
    $this->useDatabaseLocales(['en', 'ka', 'ru'], inactive: ['ru']);
    $this->localizedRoutes(fn (Router $router) => $router->get('/wines', fn () => 'wines')->name('wines'));

    $this->get('/ru/wines')->assertNotFound();
    $this->get('/fr/wines')->assertNotFound();
    $this->get('/ENGLISH/wines')->assertNotFound();
});

it('serves locales that are created or activated at runtime without re-registering routes', function () {
    $this->useDatabaseLocales(['en', 'ka'], inactive: ['ka']);
    $this->localizedRoutes(fn (Router $router) => $router->get('/wines', fn () => 'wines:'.app()->getLocale())->name('wines'));

    $this->get('/ka/wines')->assertNotFound();

    resolve(ActivateLocale::class)->execute('ka');
    resolve(CreateLocale::class)->execute(new CreateLocaleData('fr', 'French', 'Français', active: true));

    $this->get('/ka/wines')->assertSeeText('wines:ka');
    $this->get('/fr/wines')->assertSeeText('wines:fr');
});

it('B6: permanently redirects GET and HEAD requests for the prefixed default locale, keeping the query', function () {
    registerSiteRoutes();

    $this->get('/en/wines/10?page=2')->assertStatus(301)->assertRedirect('/wines/10?page=2');
    $this->get('/en')->assertStatus(301)->assertRedirect('/');
    $this->call('HEAD', '/en/faq')->assertStatus(301);
});

it('B6: does not redirect non-GET requests for the prefixed default locale', function () {
    registerSiteRoutes();

    $this->post('/en/contact')->assertOk()->assertSeeText('contact:en');
    $this->post('/ka/contact')->assertOk()->assertSeeText('contact:ka');
});

it('B5: requires every localized route to be named', function () {
    $this->localizedRoutes(function (Router $router) {
        $router->get('/first', fn () => 'first')->name('first');
        $router->get('/second', fn () => 'second');
    });
})->throws(InvalidLocalizedRouteException::class, 'Localized route [second] must be named');

it('only registers prefixed routes when the default locale is visible', function () {
    $this->configureLocalization(['hide_default_locale' => false]);
    $this->localizedRoutes(function (Router $router) {
        $router->get('/', fn () => 'home:'.app()->getLocale())->name('home');
        $router->get('/wines', fn () => 'wines:'.app()->getLocale())->name('wines');
    });

    $this->get('/en/wines')->assertOk()->assertSeeText('wines:en');
    $this->get('/ka/wines')->assertOk()->assertSeeText('wines:ka');
    $this->get('/wines')->assertNotFound();
    expect(Localization::route('wines', locale: 'en', absolute: false))->toBe('/en/wines');
});

it('B18: redirects "/" to the default locale root when every locale is prefixed', function () {
    $this->configureLocalization(['hide_default_locale' => false]);
    $this->localizedRoutes(fn (Router $router) => $router->get('/', fn () => 'home')->name('home'));

    $this->withHeader('Accept-Language', 'ka')->get('/')->assertRedirect('/en');
});

it('B18: negotiates "/" when every locale is prefixed and negotiation is enabled', function () {
    $this->configureLocalization(['hide_default_locale' => false, 'negotiate_root_locale' => true]);
    $this->localizedRoutes(fn (Router $router) => $router->get('/', fn () => 'home')->name('home'));

    $response = $this->withHeader('Accept-Language', 'ka')->get('/')->assertRedirect('/ka');

    expect($response->baseResponse->getVary())->toContain('Accept-Language', 'Cookie');
});

it('routes identically when routes are cached (compiled)', function () {
    registerSiteRoutes();

    $router = app('router');
    $router->setCompiledRoutes($router->getRoutes()->compile());

    $this->get('/ka')->assertSeeText('home:ka');
    $this->get('/faq')->assertSeeText('faq:en');
    $this->get('/ka/faq')->assertSeeText('faq:ka');
    $this->get('/ka/news/hello')->assertSeeText('post:ka:news:hello');
    $this->get('/about-us')->assertSeeText('page:en:about-us');
    $this->get('/en/wines/10')->assertRedirect('/wines/10');
});

it('serves runtime-created locales from cached routes', function () {
    $this->useDatabaseLocales(['en']);
    $this->localizedRoutes(fn (Router $router) => $router->get('/wines', fn () => 'wines:'.app()->getLocale())->name('wines'));

    $router = app('router');
    $router->setCompiledRoutes($router->getRoutes()->compile());

    resolve(CreateLocale::class)->execute(new CreateLocaleData('fr', 'French', 'Français', active: true));

    $this->get('/fr/wines')->assertSeeText('wines:fr');
});

it('documents the limitation: a locale-shaped slug in a leading catch-all reaches the prefixed variant', function () {
    $this->localizedRoutes(function (Router $router) {
        $router->get('/', fn () => 'home')->name('home');
        $router->get('/{page}', fn (string $page) => 'page:'.$page)->name('page');
    });

    // "/map" is a page slug, but it is also shaped like a locale code. The
    // router cannot know at match time (especially with cached routes), so
    // the prefixed home route claims it. Constrain such parameters instead.
    $this->get('/map')->assertNotFound();
    $this->get('/about-us')->assertSeeText('page:about-us');
});

it('does not touch the locales table when routes are registered', function () {
    $this->useDatabaseLocales(['en']);

    DB::enableQueryLog();
    registerSiteRoutes();

    expect(DB::getQueryLog())->toBe([]);
});
