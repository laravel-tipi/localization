<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Tipi\Localization\Config\LocalizationConfig;
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
    ]);

    $this->app->forgetInstance(LocalizationConfig::class);

    config()->set('localization.negotiate_root_locale', true);

    resolve(LocalizedRouteRegistrar::class)->routes(function (Router $router) {
        $router->get('/', fn () => app()->getLocale())
            ->name('home');

        $router->get('/about', fn () => app()->getLocale())
            ->name('about');
    });

    app('router')->getRoutes()->refreshNameLookups();
});

it('redirects the root route to the preferred non-default browser locale', function () {
    $this->withHeader('Accept-Language', 'ka')
        ->get('/')
        ->assertRedirect('/ka');
});

it('does not redirect when the preferred browser locale is the default', function () {
    $this->withHeader('Accept-Language', 'en')
        ->get('/')
        ->assertOk()
        ->assertSeeText('en');
});

it('prefers a remembered locale over the browser locale', function () {
    $this->withUnencryptedCookie('locale', 'ka')
        ->withHeader('Accept-Language', 'en')
        ->get('/')
        ->assertRedirect('/ka');
});

it('only negotiates the configured root route', function () {
    $this->withHeader('Accept-Language', 'ka')
        ->get('/about')
        ->assertOk()
        ->assertSeeText('en');
});
