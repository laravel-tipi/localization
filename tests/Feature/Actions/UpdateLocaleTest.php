<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tipi\Localization\Actions\UpdateLocale;
use Tipi\Localization\Data\UpdateLocaleData;
use Tipi\Localization\LocaleRegistry;
use Tipi\Localization\Models\Locale;
use Tipi\Support\Enums\TextDirection;

beforeEach(function () {
    $this->artisan('migrate')->run();

    DB::table('locales')->insert([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'country_code' => 'GB',
        'text_direction' => 'ltr',
        'is_active' => true,
        'is_default' => true,
    ]);
});

it('updates a locale', function () {
    $locale = resolve(UpdateLocale::class)->execute(
        'en',
        new UpdateLocaleData(
            name: 'Updated English',
            nativeName: 'Updated Native Name',
            countryCode: 'US',
            textDirection: TextDirection::Rtl,
        ),
    );

    expect($locale)
        ->toBeInstanceOf(Locale::class)
        ->code->toBe('en')
        ->name->toBe('Updated English')
        ->native_name->toBe('Updated Native Name')
        ->country_code->toBe('US')
        ->text_direction->toBe(TextDirection::Rtl);

    $this->assertDatabaseHas('locales', [
        'code' => 'en',
        'name' => 'Updated English',
        'native_name' => 'Updated Native Name',
        'country_code' => 'US',
        'text_direction' => 'rtl',
    ]);
});

it('does not change the locale code', function () {
    resolve(UpdateLocale::class)->execute(
        'en',
        new UpdateLocaleData(
            name: 'Updated English',
            nativeName: 'English',
            countryCode: 'US',
            textDirection: TextDirection::Ltr,
        ),
    );

    expect(Locale::query()->find('en'))->not->toBeNull();

    $this->assertDatabaseCount('locales', 1);
});

it('does not change active or default state', function () {
    resolve(UpdateLocale::class)->execute(
        'en',
        new UpdateLocaleData(
            name: 'Updated English',
            nativeName: 'English',
            countryCode: 'US',
            textDirection: TextDirection::Ltr,
        ),
    );

    $locale = Locale::query()->findOrFail('en');

    expect($locale)
        ->is_active->toBeTrue()
        ->is_default->toBeTrue();
});

it('allows the country code to be removed', function () {
    resolve(UpdateLocale::class)->execute(
        'en',
        new UpdateLocaleData(
            name: 'English',
            nativeName: 'English',
            countryCode: null,
            textDirection: TextDirection::Ltr,
        ),
    );

    expect(
        Locale::query()->findOrFail('en')->country_code
    )->toBeNull();
});

it('throws when the locale does not exist', function () {
    resolve(UpdateLocale::class)->execute(
        'ka',
        new UpdateLocaleData(
            name: 'Georgian',
            nativeName: 'ქართული',
            countryCode: 'GE',
            textDirection: TextDirection::Ltr,
        ),
    );
})->throws(ModelNotFoundException::class);

it('flushes the locale registry after updating a locale', function () {
    $registry = resolve(LocaleRegistry::class);

    expect($registry->get('en')->name)->toBe('English');

    resolve(UpdateLocale::class)->execute(
        'en',
        new UpdateLocaleData(
            name: 'Updated English',
            nativeName: 'English',
            countryCode: 'GB',
            textDirection: TextDirection::Ltr,
        ),
    );

    expect($registry->get('en')->name)->toBe('Updated English');
});
