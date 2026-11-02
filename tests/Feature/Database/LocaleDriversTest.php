<?php

declare(strict_types=1);

use Tipi\Localization\Exceptions\DefaultLocaleMissingException;
use Tipi\Localization\Facades\Localization;
use Tipi\Support\Enums\TextDirection;

it('exposes the same registry through both locale drivers', function (string $driver) {
    $driver === 'database'
        ? $this->useDatabaseLocales(['en', 'ka', 'ru', 'de'])
        : $this->configureLocalization([]);

    $registry = Localization::locales();

    expect($registry->all()->keys()->all())->toBe(['en', 'ka', 'ru', 'de'])
        ->and($registry->activeCodes())->toBe(['en', 'ka', 'ru', 'de'])
        ->and($registry->defaultCode())->toBe('en')
        ->and($registry->get('ka')->textDirection)->toBe(TextDirection::Ltr)
        ->and($registry->get('ka')->isDefault())->toBeFalse();
})->with(['config', 'database']);

it('B17: explains how to fix an empty database registry', function () {
    $this->configureLocalization(['locales_driver' => 'database']);

    Localization::locales()->default();
})->throws(DefaultLocaleMissingException::class, 'create the first locale with the CreateLocale action');
