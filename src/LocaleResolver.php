<?php

declare(strict_types=1);

namespace Tipi\Localization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Tipi\Localization\Contracts\TranslatableModel;
use Tipi\Localization\Exceptions\DefaultLocaleNotDefined;
use Tipi\Localization\Exceptions\LocaleNotFound;
use Tipi\Localization\Exceptions\UnsupportedLocale;
use Tipi\Localization\Models\Locale;

final class LocaleResolver
{
    /**
     * @var Collection<string, Locale>|null
     */
    protected ?Collection $locales = null;

    /**
     * @var Collection<string, Locale>|null
     */
    protected ?Collection $supportedLocales = null;

    protected ?Locale $defaultLocale = null;

    /**
     * @return Collection<string, Locale>
     */
    public function getLocales(): Collection
    {
        return $this->locales ??= Locale::query()
            ->get()
            ->keyBy('code')
            ->toBase();
    }

    public function getLocale(string $code): Locale
    {
        $locale = $this->getLocales()->get($code);

        if ($locale === null) {
            throw new LocaleNotFound(code: $code);
        }

        return $locale;
    }

    /**
     * @return Collection<string, Locale>
     */
    public function getSupportedLocales(): Collection
    {
        return $this->supportedLocales ??= $this->getLocales()
            ->filter(fn (Locale $locale): bool => $locale->isActive());
    }

    public function getSupportedCodes(): array
    {
        return $this->getSupportedLocales()->keys()->all();
    }

    public function getSupportedLocale(string $code): Locale
    {
        $locale = $this->getSupportedLocales()->get($code);
        if ($locale === null) {
            throw new UnsupportedLocale(code: $code);
        }

        return $locale;
    }

    public function getDefaultCode(): string
    {
        return $this->getDefaultLocale()->getKey();
    }

    public function getDefaultLocale(): Locale
    {
        if ($this->defaultLocale !== null) {
            return $this->defaultLocale;
        }

        $default = $this->getSupportedLocales()
            ->first(fn (Locale $locale): bool => $locale->isDefault());

        if ($default === null) {
            throw new DefaultLocaleNotDefined;
        }

        return $this->defaultLocale = $default;
    }

    public function getCurrentCode(): string
    {
        return app()->getLocale();
    }

    public function getCurrentLocale(): Locale
    {
        return $this->getSupportedLocale($this->getCurrentCode());
    }

    /**
     * @return Collection<string, Locale>
     */
    public function getMissingLocales(Model&TranslatableModel $record): Collection
    {
        $existingLocaleCodes = $record->translations()
            ->pluck('locale_code');

        return $this->getSupportedLocales()
            ->except($existingLocaleCodes);
    }
}
