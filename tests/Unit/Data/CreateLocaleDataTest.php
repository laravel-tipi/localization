<?php

declare(strict_types=1);

use Tipi\Localization\Data\CreateLocaleData;
use Tipi\Localization\Enums\TextDirection;

it('creates data from an array', function () {
    $data = CreateLocaleData::fromArray([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'country_code' => 'GB',
        'text_direction' => 'rtl',
    ]);

    expect($data)
        ->code->toBe('en')
        ->name->toBe('English')
        ->nativeName->toBe('English')
        ->countryCode->toBe('GB')
        ->textDirection->toBe(TextDirection::Rtl);
});

it('uses optional defaults when creating data from an array', function () {
    $data = CreateLocaleData::fromArray([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
    ]);

    expect($data)
        ->countryCode->toBeNull()
        ->textDirection->toBe(TextDirection::Ltr);
});

it('converts data to an array', function () {
    $data = new CreateLocaleData(
        code: 'en',
        name: 'English',
        nativeName: 'English',
        countryCode: 'GB',
        textDirection: TextDirection::Rtl,
    );

    expect($data->toArray())->toBe([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'country_code' => 'GB',
        'text_direction' => 'rtl',
    ]);
});
