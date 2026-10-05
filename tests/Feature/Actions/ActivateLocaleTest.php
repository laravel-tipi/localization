<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tipi\Localization\Actions\ActivateLocale;
use Tipi\Localization\Exceptions\LocaleCannotBeActivatedException;
use Tipi\Localization\LocaleRegistry;

beforeEach(function () {
    $this->artisan('migrate')->run();
});

it('activates an inactive locale', function () {
    DB::table('locales')->insert([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'country_code' => 'GB',
        'text_direction' => 'ltr',
        'is_active' => false,
        'is_default' => false,
    ]);

    resolve(ActivateLocale::class)->execute('en');

    $this->assertDatabaseHas('locales', [
        'code' => 'en',
        'is_active' => true,
    ]);
});

it('cannot activate an already active locale', function () {
    DB::table('locales')->insert([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'country_code' => 'GB',
        'text_direction' => 'ltr',
        'is_active' => true,
        'is_default' => true,
    ]);

    resolve(ActivateLocale::class)->execute('en');
})->throws(LocaleCannotBeActivatedException::class);

it('throws when the locale does not exist', function () {
    resolve(ActivateLocale::class)->execute('en');
})->throws(ModelNotFoundException::class);

it('flushes the locale registry after activating a locale', function () {
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
            'is_active' => false,
            'is_default' => false,
        ],
    ]);

    $registry = resolve(LocaleRegistry::class);

    // Cache the current state.
    expect($registry->supportedCodes())->toBe(['en']);

    resolve(ActivateLocale::class)->execute('ka');

    expect($registry->supportedCodes())
        ->toContain('en', 'ka');
});
