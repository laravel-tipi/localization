<?php

declare(strict_types=1);

namespace Tipi\Localization;

use Illuminate\Support\Collection;
use Tipi\Localization\Exceptions\UnsupportedLocaleException;
use Tipi\Support\Locale;

/**
 * A resolved locale context: the active locales one interface of the
 * application supports, and its default. Immutable.
 */
final readonly class LocaleContext
{
    /**
     * @param  Collection<string, Locale>  $locales
     */
    public function __construct(
        public string $name,
        private Collection $locales,
        private Locale $default,
        private bool $includesAllLocales,
    ) {}

    /**
     * The supported (active, member) locales keyed by code, in display order.
     *
     * @return Collection<string, Locale>
     */
    public function supported(): Collection
    {
        return $this->locales;
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return $this->locales->keys()->all();
    }

    public function supports(string|Locale $locale): bool
    {
        return $this->locales->has(is_string($locale) ? $locale : $locale->code);
    }

    public function find(string $code): ?Locale
    {
        return $this->locales->get($code);
    }

    /**
     * @throws UnsupportedLocaleException
     */
    public function get(string|Locale $locale): Locale
    {
        $code = is_string($locale) ? $locale : $locale->code;

        return $this->find($code) ?? throw UnsupportedLocaleException::notInContext($code, $this->name);
    }

    /**
     * The context default. It is always supported by the context and may
     * differ from the global default.
     */
    public function default(): Locale
    {
        return $this->default;
    }

    public function defaultCode(): string
    {
        return $this->default->code;
    }

    /**
     * Whether the locale is this context's default. Locale::isDefault()
     * answers for the global default instead.
     */
    public function isDefault(string|Locale $locale): bool
    {
        return (is_string($locale) ? $locale : $locale->code) === $this->default->code;
    }

    public function includesAllLocales(): bool
    {
        return $this->includesAllLocales;
    }
}
