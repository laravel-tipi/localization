<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tipi\Localization\Actions\MakeLocaleDefault;
use Tipi\Localization\Exceptions\LocaleCannotBeMadeDefaultException;
use Tipi\Localization\LocaleRegistry;

beforeEach(function () {
    $this->artisan('migrate')->run();
});

it('makes an active non-default locale the default', function () {
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

    resolve(MakeLocaleDefault::class)->execute('ka');

    $this->assertDatabaseHas('locales', [
        'code' => 'ka',
        'is_default' => true,
    ]);

    $this->assertDatabaseHas('locales', [
        'code' => 'en',
        'is_default' => false,
    ]);
});

it('leaves exactly one default locale', function () {
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

    resolve(MakeLocaleDefault::class)->execute('ka');

    expect(
        DB::table('locales')
            ->where('is_default', true)
            ->count()
    )->toBe(1);
});

it('cannot make an inactive locale default', function () {
    DB::table('locales')->insert([
        'code' => 'ka',
        'name' => 'Georgian',
        'native_name' => 'ქართული',
        'country_code' => 'GE',
        'text_direction' => 'ltr',
        'is_active' => false,
        'is_default' => false,
    ]);

    resolve(MakeLocaleDefault::class)->execute('ka');
})->throws(LocaleCannotBeMadeDefaultException::class);

it('cannot make the current default locale default again', function () {
    DB::table('locales')->insert([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'country_code' => 'GB',
        'text_direction' => 'ltr',
        'is_active' => true,
        'is_default' => true,
    ]);

    resolve(MakeLocaleDefault::class)->execute('en');
})->throws(LocaleCannotBeMadeDefaultException::class);

it('throws when the locale does not exist', function () {
    resolve(MakeLocaleDefault::class)->execute('ka');
})->throws(ModelNotFoundException::class);

it('flushes the locale registry after changing the default locale', function () {
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

    $registry = resolve(LocaleRegistry::class);

    // Populate the registry cache.
    expect($registry->defaultCode())->toBe('en');

    resolve(MakeLocaleDefault::class)->execute('ka');

    expect($registry->defaultCode())->toBe('ka');
});
