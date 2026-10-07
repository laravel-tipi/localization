<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Tipi\Localization\Contracts\LocaleRepository;
use Tipi\Localization\Exceptions\DefaultLocaleNotDefinedException;
use Tipi\Localization\Exceptions\LocaleNotFoundException;
use Tipi\Localization\Exceptions\UnsupportedLocaleException;
use Tipi\Localization\LocaleRegistry;
use Tipi\Support\Enums\TextDirection;
use Tipi\Support\Locale;

function makeLocale(
    string $code,
    bool $active = true,
    bool $default = false,
): Locale {
    return new Locale(
        code: $code,
        name: $code,
        nativeName: $code,
        countryCode: null,
        textDirection: TextDirection::Ltr,
        active: $active,
        default: $default,
    );
}

function makeRegistry(array $locales): LocaleRegistry
{
    $repository = new class($locales) implements LocaleRepository
    {
        public int $calls = 0;

        public function __construct(
            private readonly array $locales,
        ) {}

        public function all(): Collection
        {
            $this->calls++;

            return collect($this->locales)
                ->keyBy(fn (Locale $locale): string => $locale->code);
        }
    };

    return new LocaleRegistry($repository);
}

it('returns all locales', function () {
    $registry = makeRegistry([
        makeLocale('en'),
        makeLocale('ka'),
    ]);

    expect($registry->all())
        ->toHaveCount(2)
        ->toHaveKeys(['en', 'ka']);
});

it('returns a locale by code', function () {
    $registry = makeRegistry([
        makeLocale('en'),
    ]);

    expect($registry->get('en')->code)->toBe('en');
});

it('throws when a locale does not exist', function () {
    makeRegistry([])->get('en');
})->throws(LocaleNotFoundException::class);

it('returns only active locales as supported', function () {
    $registry = makeRegistry([
        makeLocale('en', active: true),
        makeLocale('ka', active: false),
    ]);

    expect($registry->supported())
        ->toHaveCount(1)
        ->toHaveKey('en')
        ->not->toHaveKey('ka');

    expect($registry->supportedCodes())->toBe(['en']);
});

it('returns a supported locale', function () {
    $locale = makeRegistry([
        makeLocale('en', active: true),
    ])->supportedLocale('en');

    expect($locale->code)->toBe('en');
});

it('throws when a locale is inactive', function () {
    makeRegistry([
        makeLocale('en', active: false),
    ])->supportedLocale('en');
})->throws(UnsupportedLocaleException::class);

it('returns the default locale', function () {
    $registry = makeRegistry([
        makeLocale('en'),
        makeLocale('ka', default: true),
    ]);

    expect($registry->default())
        ->code->toBe('ka');

    expect($registry->defaultCode())->toBe('ka');
});

it('throws when no default locale is defined', function () {
    makeRegistry([
        makeLocale('en'),
    ])->default();
})->throws(DefaultLocaleNotDefinedException::class);

it('caches locales until flushed', function () {
    $repository = new class implements LocaleRepository
    {
        public int $calls = 0;

        public function all(): Collection
        {
            $this->calls++;

            return collect([
                'en' => makeLocale('en'),
            ]);
        }
    };

    $registry = new LocaleRegistry($repository);

    $registry->all();
    $registry->all();

    expect($repository->calls)->toBe(1);

    $registry->flush();
    $registry->all();

    expect($repository->calls)->toBe(2);
});
