<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Tipi\Localization\Exceptions\LocaleNotFoundException;
use Tipi\Localization\Exceptions\UnsupportedLocaleException;
use Tipi\Localization\LocaleResolver;

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

it('resolves the current application locale', function () {
    app()->setLocale('ka');

    $locale = resolve(LocaleResolver::class)->current();

    expect($locale->code)->toBe('ka');
});

it('returns the current locale code', function () {
    app()->setLocale('ka');

    expect(
        resolve(LocaleResolver::class)->currentCode()
    )->toBe('ka');
});

it('throws when the application locale does not exist', function () {
    app()->setLocale('de');

    resolve(LocaleResolver::class)->current();
})->throws(LocaleNotFoundException::class);

it('throws when the application locale is inactive', function () {
    app()->setLocale('ru');

    resolve(LocaleResolver::class)->current();
})->throws(UnsupportedLocaleException::class);

it('caches the resolved locale', function () {
    app()->setLocale('en');

    $resolver = resolve(LocaleResolver::class);

    expect($resolver->currentCode())->toBe('en');

    app()->setLocale('ka');

    expect($resolver->currentCode())->toBe('en');
});
