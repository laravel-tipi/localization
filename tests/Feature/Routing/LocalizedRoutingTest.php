<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
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
        $router->get('/', fn () => app()->getLocale())
            ->name('home');

        $router->get('/about', fn () => app()->getLocale())
            ->name('about');

        $router->get(
            '/wines/{wine}',
            fn (string $wine) => app()->getLocale().':'.$wine,
        )->name('wines.show');
    });

    app('router')->getRoutes()->refreshNameLookups();
});

it('serves the default locale without a locale prefix', function () {
    $this->get('/about')
        ->assertOk()
        ->assertSeeText('en');
});

it('serves a supported non-default locale with a locale prefix', function () {
    $this->get('/ka/about')
        ->assertOk()
        ->assertSeeText('ka');
});

it('redirects a prefixed default locale to the unprefixed route', function () {
    $this->get('/en/about')
        ->assertRedirect('/about');
});

it('preserves route parameters when redirecting the default locale', function () {
    $this->get('/en/wines/10')
        ->assertRedirect('/wines/10');
});

it('preserves query parameters when redirecting the default locale', function () {
    $this->get('/en/wines/10?page=2')
        ->assertRedirect('/wines/10?page=2');
});

it('does not allow an inactive locale', function () {
    $this->get('/ru/about')
        ->assertNotFound();
});

it('does not allow an unknown locale', function () {
    $this->get('/de/about')
        ->assertNotFound();
});

it('remembers the default locale in a cookie', function () {
    $this->get('/about')
        ->assertOk()
        ->assertPlainCookie('locale', 'en');
});

it('remembers a non-default locale in a cookie', function () {
    $this->get('/ka/about')
        ->assertOk()
        ->assertPlainCookie('locale', 'ka');
});

it('does not match an invalid locale code', function () {
    $this->get('/ENGLISH/about')
        ->assertNotFound();
});

it('does not negotiate the root route when negotiation is disabled', function () {
    $this->withHeader('Accept-Language', 'ka')
        ->get('/')
        ->assertOk()
        ->assertSeeText('en');
});
