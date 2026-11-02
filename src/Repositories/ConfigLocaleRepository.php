<?php

declare(strict_types=1);

namespace Tipi\Localization\Repositories;

use Illuminate\Support\Collection;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Contracts\LocaleRepository;
use Tipi\Support\Locale;

/**
 * Reads the locales declared in config/localization.php. They were
 * validated when LocalizationConfig was built; every one is active.
 */
final readonly class ConfigLocaleRepository implements LocaleRepository
{
    public function __construct(
        private LocalizationConfig $config,
    ) {}

    public function all(): Collection
    {
        return (new Collection($this->config->locales))->mapWithKeys(
            fn (array $locale): array => [
                $locale['code'] => new Locale(
                    code: $locale['code'],
                    name: $locale['name'],
                    nativeName: $locale['native_name'],
                    nativeAbbreviation: $locale['native_abbreviation'],
                    countryCode: $locale['country_code'],
                    textDirection: $locale['text_direction'],
                    active: true,
                    default: $locale['code'] === $this->config->defaultLocale,
                ),
            ],
        );
    }
}
