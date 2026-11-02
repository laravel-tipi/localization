<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Facade;
use Tipi\Localization\Exceptions\InvalidLocaleContextException;
use Tipi\Localization\Exceptions\LocaleContextNotFoundException;
use Tipi\Localization\Exceptions\UnsupportedLocaleException;
use Tipi\Localization\Facades\Localization;

it('provides a "web" context with every active locale when none is configured', function () {
    $this->configureLocalization(['contexts' => []]);

    $context = Localization::context();

    expect($context->name)->toBe('web')
        ->and($context->codes())->toBe(['en', 'ka', 'ru', 'de'])
        ->and($context->includesAllLocales())->toBeTrue()
        ->and($context->defaultCode())->toBe('en')
        ->and(Localization::contexts()->names())->toBe(['web']);
});

it('limits a context to its locales, in global order, with its own default', function () {
    $this->configureLocalization(['contexts' => ['admin' => ['locales' => ['ka', 'en'], 'default' => 'ka']]]);

    $admin = Localization::context('admin');

    expect($admin->codes())->toBe(['en', 'ka'])
        ->and($admin->defaultCode())->toBe('ka')
        ->and($admin->supports('ru'))->toBeFalse()
        ->and($admin->find('ru'))->toBeNull()
        ->and(fn () => $admin->get('ru'))->toThrow(UnsupportedLocaleException::class)
        ->and(Localization::locales()->activeCodes())->toBe(['en', 'ka', 'ru', 'de']);
});

it('keeps the context default separate from the global default', function () {
    $this->configureLocalization(['contexts' => ['admin' => ['locales' => ['ka', 'en'], 'default' => 'ka']]]);

    $admin = Localization::context('admin');
    $en = $admin->get('en');

    expect($admin->isDefault('ka'))->toBeTrue()
        ->and($admin->isDefault($admin->get('ka')))->toBeTrue()
        ->and($admin->isDefault($en))->toBeFalse()
        ->and($en->isDefault())->toBeTrue() // Locale::isDefault() is the global default
        ->and(Localization::context('web')->isDefault($en))->toBeTrue();
});

it('follows the global default when a context has no default', function () {
    $this->configureLocalization(['contexts' => ['admin' => ['locales' => ['en', 'ka']]]]);

    expect(Localization::context('admin')->defaultCode())->toBe('en');
});

it('never offers inactive locales, even when a context lists them', function () {
    $this->useDatabaseLocales(['en', 'ka', 'ru'], inactive: ['ru']);
    $this->configureLocalization(['contexts' => ['admin' => ['locales' => ['en', 'ru']]]]);

    expect(Localization::context('admin')->codes())->toBe(['en'])
        ->and(Localization::context()->codes())->toBe(['en', 'ka']);
});

it('rejects context definitions that break an invariant', function (array $context, string $message) {
    $this->configureLocalization(['contexts' => ['admin' => $context]]);

    expect(fn () => Localization::context('admin'))->toThrow(InvalidLocaleContextException::class, $message);
})->with([
    'missing global default' => [['locales' => ['ka']], 'must contain the global default locale [en]'],
    'default outside context' => [['locales' => ['en', 'ka'], 'default' => 'ru'], 'default locale [ru] is not one of its locales'],
    'unknown locale' => [['locales' => ['en', 'fr']], 'locale [fr] does not exist in the locale registry'],
    'unknown default' => [['default' => 'fr'], 'default locale [fr] is not one of its locales'],
]);

it('throws for unknown contexts', function () {
    Localization::context('missing');
})->throws(LocaleContextNotFoundException::class, 'Locale context [missing] does not exist.');

it('switches the current context and resets it for every request', function () {
    $this->configureLocalization(['contexts' => ['admin' => ['locales' => ['en', 'ka']]]]);

    Localization::contexts()->setCurrent('admin');
    expect(Localization::context()->name)->toBe('admin');

    $this->app->forgetScopedInstances();
    Facade::clearResolvedInstances();

    expect(Localization::context()->name)->toBe('web');
});

it('falls back to the global default when a stored context default became inactive', function () {
    $this->useDatabaseLocales(['en', 'ka'], inactive: ['ka']);
    $this->configureLocalization(['contexts' => ['admin' => ['locales' => ['en', 'ka'], 'default' => 'ka']]]);

    expect(Localization::context('admin')->defaultCode())->toBe('en');
});
