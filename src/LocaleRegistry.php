<?php

declare(strict_types=1);

namespace Tipi\Localization;

use Illuminate\Support\Collection;
use Tipi\Localization\Contracts\LocaleRepository;
use Tipi\Localization\Exceptions\DefaultLocaleNotDefinedException;
use Tipi\Localization\Exceptions\LocaleNotFoundException;
use Tipi\Localization\Exceptions\UnsupportedLocaleException;

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
     * @return Collection<string, Locale>
     */
    public function all(): Collection
    {
        return $this->locales ??= $this->repository->all();
    }

    public function get(string $code): Locale
    {
        $locale = $this->all()->get($code);

        if ($locale === null) {
            throw new LocaleNotFoundException(code: $code);
        }

        return $locale;
    }

    /**
     * @return Collection<string, Locale>
     */
    public function supported(): Collection
    {
        return $this->all()
            ->filter(
                fn (Locale $locale): bool => $locale->isActive(),
            );
    }

    public function supportedCodes(): array
    {
        return $this->supported()->keys()->all();
    }

    public function supportedLocale(string $code): Locale
    {
        $locale = $this->get($code);

        if (! $locale->isActive()) {
            throw new UnsupportedLocaleException(code: $code);
        }

        return $locale;
    }

    public function default(): Locale
    {
        $locale = $this->all()
            ->first(
                fn (Locale $locale): bool => $locale->isDefault(),
            );

        if ($locale === null) {
            throw new DefaultLocaleNotDefinedException;
        }

        return $locale;
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
