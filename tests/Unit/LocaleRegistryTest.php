<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Tipi\Localization\Contracts\LocaleRepository;
use Tipi\Localization\Exceptions\DefaultLocaleMissingException;
use Tipi\Localization\Exceptions\LocaleNotFoundException;
use Tipi\Localization\Exceptions\UnsupportedLocaleException;
use Tipi\Localization\LocaleRegistry;
use Tipi\Support\Enums\TextDirection;
use Tipi\Support\Locale;

function registryLocale(string $code, bool $active = true, bool $default = false): Locale
{
    return new Locale($code, $code, $code, null, null, TextDirection::Ltr, $active, $default);
}

function registryWith(Locale ...$locales): LocaleRegistry
{
    return new LocaleRegistry(new class($locales) implements LocaleRepository
    {
        public int $calls = 0;

        public function __construct(private readonly array $locales) {}

        public function all(): Collection
        {
            $this->calls++;

            return collect($this->locales)->keyBy('code');
        }
    });
}

it('separates all locales from active ones, keeping order', function () {
    $registry = registryWith(registryLocale('ka'), registryLocale('ru', active: false), registryLocale('en', default: true));

    expect($registry->all()->keys()->all())->toBe(['ka', 'ru', 'en'])
        ->and($registry->activeCodes())->toBe(['ka', 'en'])
        ->and($registry->isActive('ru'))->toBeFalse()
        ->and($registry->isActive('fr'))->toBeFalse()
        ->and($registry->find('fr'))->toBeNull()
        ->and($registry->get('ru')->code)->toBe('ru')
        ->and($registry->defaultCode())->toBe('en');
});

it('throws precise exceptions', function () {
    $registry = registryWith(registryLocale('en', default: true), registryLocale('ru', active: false));

    expect(fn () => $registry->get('fr'))->toThrow(LocaleNotFoundException::class, 'Locale [fr] does not exist.')
        ->and(fn () => $registry->getActive('ru'))->toThrow(UnsupportedLocaleException::class, 'Locale [ru] is not active.');
});

it('never returns an inactive default and explains how to fix a missing one', function () {
    expect(fn () => registryWith(registryLocale('en', active: false, default: true))->default())
        ->toThrow(DefaultLocaleMissingException::class, 'CreateLocale');
});

it('memoizes locales until flushed', function () {
    $registry = registryWith(registryLocale('en', default: true));
    $repository = (fn () => $this->repository)->call($registry);

    $registry->all();
    $registry->all();
    $registry->flush();
    $registry->all();

    expect($repository->calls)->toBe(2);
});
