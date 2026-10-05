<?php

declare(strict_types=1);

use Tipi\Localization\Data\UpdateLocaleData;
use Tipi\Localization\Enums\TextDirection;

it('creates data from an array', function () {
    $data = UpdateLocaleData::fromArray([
        'name' => 'English',
        'native_name' => 'English',
        'country_code' => 'GB',
        'text_direction' => 'rtl',
    ]);

    expect($data)
        ->name->toBe('English')
        ->nativeName->toBe('English')
        ->countryCode->toBe('GB')
        ->textDirection->toBe(TextDirection::Rtl);
});

it('uses optional defaults when creating data from an array', function () {
    $data = UpdateLocaleData::fromArray([
        'name' => 'English',
        'native_name' => 'English',
    ]);

    expect($data)
        ->countryCode->toBeNull()
        ->textDirection->toBe(TextDirection::Ltr);
});

it('converts data to an array', function () {
    $data = new UpdateLocaleData(
        name: 'English',
        nativeName: 'English',
        countryCode: 'GB',
        textDirection: TextDirection::Rtl,
    );

    expect($data->toArray())->toBe([
        'name' => 'English',
        'native_name' => 'English',
        'country_code' => 'GB',
        'text_direction' => 'rtl',
    ]);
});
