<?php

declare(strict_types=1);

use Tipi\Localization\Support\ContextName;
use Tipi\Localization\Support\LocaleCode;

it('accepts supported locale codes', function (string $code) {
    expect(LocaleCode::isValid($code))->toBeTrue();
})->with(['en', 'eng', 'en-US', 'en-001', 'zh-Hans', 'zh-Hans-CN', 'yue-Hant-001']);

it('rejects unsupported locale codes', function (string $code) {
    expect(LocaleCode::isValid($code))->toBeFalse();
})->with(['', 'EN', 'e', 'engl', 'en-us', 'zh-hans', 'en-01', 'en_US', 'en-', "en\n"]);

it('never accepts a code longer than the declared maximum (column fits every code)', function () {
    expect(strlen('yue-Hant-001'))->toBe(LocaleCode::MAX_LENGTH)
        ->and(LocaleCode::isValid('yue-Hant-0012'))->toBeFalse();
});

it('validates context names', function (string $name, bool $valid) {
    expect(ContextName::isValid($name))->toBe($valid);
})->with([
    ['web', true],
    ['admin-panel_2', true],
    ['Admin', false],
    ['2fa', false],
    ['', false],
    [str_repeat('a', 65), false],
]);
