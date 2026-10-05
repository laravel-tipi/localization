<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tipi\Localization\LocaleNegotiator;

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
});

it('uses a supported locale from the cookie', function () {
    $request = Request::create('/');

    $request->cookies->set('locale', 'ka');

    $locale = resolve(LocaleNegotiator::class)
        ->negotiate($request);

    expect($locale->code)->toBe('ka');
});

it('prefers the cookie over the browser language', function () {
    $request = Request::create(
        '/',
        server: [
            'HTTP_ACCEPT_LANGUAGE' => 'en',
        ],
    );

    $request->cookies->set('locale', 'ka');

    $locale = resolve(LocaleNegotiator::class)
        ->negotiate($request);

    expect($locale->code)->toBe('ka');
});

it('uses the preferred browser language when there is no cookie', function () {
    $request = Request::create(
        '/',
        server: [
            'HTTP_ACCEPT_LANGUAGE' => 'ka,en;q=0.8',
        ],
    );

    $locale = resolve(LocaleNegotiator::class)
        ->negotiate($request);

    expect($locale->code)->toBe('ka');
});

it('ignores an unknown locale stored in the cookie', function () {
    $request = Request::create(
        '/',
        server: [
            'HTTP_ACCEPT_LANGUAGE' => 'ka',
        ],
    );

    $request->cookies->set('locale', 'de');

    $locale = resolve(LocaleNegotiator::class)
        ->negotiate($request);

    expect($locale->code)->toBe('ka');
});

it('ignores an inactive locale stored in the cookie', function () {
    $request = Request::create(
        '/',
        server: [
            'HTTP_ACCEPT_LANGUAGE' => 'ka',
        ],
    );

    $request->cookies->set('locale', 'ru');

    $locale = resolve(LocaleNegotiator::class)
        ->negotiate($request);

    expect($locale->code)->toBe('ka');
});

it('does not negotiate an inactive browser locale', function () {
    $request = Request::create(
        '/',
        server: [
            'HTTP_ACCEPT_LANGUAGE' => 'ru,ka;q=0.8',
        ],
    );

    $locale = resolve(LocaleNegotiator::class)
        ->negotiate($request);

    expect($locale->code)->toBe('ka');
});

it('falls back to the default locale', function () {
    $request = Request::create(
        '/',
        server: [
            'HTTP_ACCEPT_LANGUAGE' => 'de',
        ],
    );

    $locale = resolve(LocaleNegotiator::class)
        ->negotiate($request);

    expect($locale->code)->toBe('en');
});
