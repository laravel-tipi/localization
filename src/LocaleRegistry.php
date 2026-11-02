<?php

declare(strict_types=1);

namespace Tipi\Localization;

use Illuminate\Support\Collection;
use Tipi\Localization\Contracts\LocaleRepository;
use Tipi\Localization\Exceptions\DefaultLocaleMissingException;
use Tipi\Localization\Exceptions\LocaleNotFoundException;
use Tipi\Localization\Exceptions\UnsupportedLocaleException;
use Tipi\Support\Locale;

/**
 * The global locale registry: every locale that exists, whether it is
 * active, and the global default. It is independent from contexts.
 *
 * Memoized for one request/job (scoped binding); flush() after writes.
 */
final class LocaleRegistry
{
    /**
     * @var Collection<string, Locale>|null
     */
    private ?Collection $locales = null;

    public function __construct(
        private readonly LocaleRepository $repository,
    ) {}

    /**
     * Every locale, active or not, keyed by code in display order.
     *
     * @return Collection<string, Locale>
     */
    public function all(): Collection
    {
        return $this->locales ??= $this->repository->all();
    }

    /**
     * @return Collection<string, Locale>
     */
    public function active(): Collection
    {
        return $this->all()->filter(
            fn (Locale $locale): bool => $locale->isActive(),
        );
    }

    /**
     * @return list<string>
     */
    public function activeCodes(): array
    {
        return $this->active()->keys()->all();
    }

    public function find(string $code): ?Locale
    {
        return $this->all()->get($code);
    }

    /**
     * @throws LocaleNotFoundException
     */
    public function get(string $code): Locale
    {
        return $this->find($code) ?? throw new LocaleNotFoundException($code);
    }

    public function isActive(string $code): bool
    {
        return $this->find($code)?->isActive() === true;
    }

    /**
     * @throws LocaleNotFoundException
     * @throws UnsupportedLocaleException
     */
    public function getActive(string $code): Locale
    {
        $locale = $this->get($code);

        if (! $locale->isActive()) {
            throw UnsupportedLocaleException::inactive($code);
        }

        return $locale;
    }

    /**
     * The global default locale. It is always active.
     *
     * @throws DefaultLocaleMissingException
     */
    public function default(): Locale
    {
        $locale = $this->all()->first(
            fn (Locale $locale): bool => $locale->isDefault() && $locale->isActive(),
        );

        return $locale ?? throw new DefaultLocaleMissingException;
    }

    public function defaultCode(): string
    {
        return $this->default()->code;
    }

    public function flush(): void
    {
        $this->locales = null;
    }
}
