<?php

declare(strict_types=1);

use Tipi\Localization\Support\LocaleCode;

it('accepts valid locale codes', function (string $code) {
    expect(LocaleCode::isValid($code))->toBeTrue();
})->with([
    'two-letter language' => 'en',
    'three-letter language' => 'eng',
    'language with region' => 'en-US',
    'language with numeric region' => 'en-001',
    'language with script' => 'zh-Hans',
    'language with script and region' => 'zh-Hans-CN',
]);

it('rejects invalid locale codes', function (string $code) {
    expect(LocaleCode::isValid($code))->toBeFalse();
})->with([
    'empty' => '',
    'uppercase language' => 'EN',
    'single-letter language' => 'e',
    'four-letter language' => 'engl',
    'lowercase region' => 'en-us',
    'lowercase script' => 'zh-hans',
    'invalid numeric region' => 'en-01',
    'underscore separator' => 'en_US',
    'trailing separator' => 'en-',
]);
