<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tipi\Localization\Actions\DeactivateLocale;
use Tipi\Localization\Exceptions\LocaleCannotBeDeactivatedException;
use Tipi\Localization\LocaleRegistry;

beforeEach(function () {
    $this->artisan('migrate')->run();
});

it('deactivates an active non-default locale', function () {
    DB::table('locales')->insert([
        'code' => 'ka',
        'name' => 'Georgian',
        'native_name' => 'ქართული',
        'country_code' => 'GE',
        'text_direction' => 'ltr',
        'is_active' => true,
        'is_default' => false,
    ]);

    resolve(DeactivateLocale::class)->execute('ka');

    $this->assertDatabaseHas('locales', [
        'code' => 'ka',
        'is_active' => false,
    ]);
});

it('cannot deactivate the default locale', function () {
    DB::table('locales')->insert([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'country_code' => 'GB',
        'text_direction' => 'ltr',
        'is_active' => true,
        'is_default' => true,
    ]);

    resolve(DeactivateLocale::class)->execute('en');
})->throws(LocaleCannotBeDeactivatedException::class);

it('cannot deactivate an already inactive locale', function () {
    DB::table('locales')->insert([
        'code' => 'ka',
        'name' => 'Georgian',
        'native_name' => 'ქართული',
        'country_code' => 'GE',
        'text_direction' => 'ltr',
        'is_active' => false,
        'is_default' => false,
    ]);

    resolve(DeactivateLocale::class)->execute('ka');
})->throws(LocaleCannotBeDeactivatedException::class);

it('throws when the locale does not exist', function () {
    resolve(DeactivateLocale::class)->execute('ka');
})->throws(ModelNotFoundException::class);

it('flushes the locale registry after deactivating a locale', function () {
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

    // Populate the registry cache before the database changes.
    expect($registry->supportedCodes())
        ->toContain('en', 'ka');

    resolve(DeactivateLocale::class)->execute('ka');

    expect($registry->supportedCodes())
        ->toBe(['en']);
});
