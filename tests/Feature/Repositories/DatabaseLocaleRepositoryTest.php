<?php

declare(strict_types=1);

use Tipi\Localization\Enums\TextDirection;
use Tipi\Localization\Repositories\DatabaseLocaleRepository;

beforeEach(function () {
    $this->artisan('migrate')->run();
});

it('returns locales keyed by their code', function () {
    DB::table('locales')->insert([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'country_code' => 'GB',
        'text_direction' => 'ltr',
        'is_active' => true,
        'is_default' => true,
    ]);

    DB::table('locales')->insert([
        'code' => 'ka',
        'name' => 'Georgian',
        'native_name' => 'ქართული',
        'country_code' => 'GE',
        'text_direction' => 'ltr',
        'is_active' => true,
        'is_default' => false,
    ]);

    $locales = (new DatabaseLocaleRepository)->all();

    expect($locales)
        ->toHaveCount(2)
        ->toHaveKeys(['en', 'ka']);
});

it('converts locale models to locale objects', function () {
    DB::table('locales')->insert([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'country_code' => 'GB',
        'text_direction' => 'ltr',
        'is_active' => true,
        'is_default' => true,
    ]);

    $locale = (new DatabaseLocaleRepository)
        ->all()
        ->get('en');

    expect($locale)
        ->code->toBe('en')
        ->name->toBe('English')
        ->nativeName->toBe('English')
        ->countryCode->toBe('GB')
        ->textDirection->toBe(TextDirection::Ltr)
        ->isActive()->toBeTrue()
        ->isDefault()->toBeTrue();
});

it('returns inactive locales as well', function () {
    DB::table('locales')->insert([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'is_active' => false,
        'is_default' => false,
    ]);

    $locale = (new DatabaseLocaleRepository)
        ->all()
        ->get('en');

    expect($locale->isActive())->toBeFalse();
});
