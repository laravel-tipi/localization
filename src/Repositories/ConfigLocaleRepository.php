<?php

declare(strict_types=1);

namespace Tipi\Localization\Repositories;

use Illuminate\Support\Collection;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Contracts\LocaleRepository;
use Tipi\Localization\Enums\TextDirection;
use Tipi\Localization\Locale;

final readonly class ConfigLocaleRepository implements LocaleRepository
{
    public function __construct(
        private LocalizationConfig $config,
    ) {}

    /**
     * @return Collection<string, Locale>
     */
    public function all(): Collection
    {
        $locales = new Collection;

        foreach ($this->config->locales as $config) {
            $locale = new Locale(
                code: (string) $config['code'],
                name: (string) $config['name'],
                nativeName: (string) $config['native_name'],
                countryCode: isset($config['country_code'])
                    ? (string) $config['country_code']
                    : null,
                textDirection: TextDirection::from(
                    $config['text_direction'] ?? TextDirection::Ltr->value,
                ),
                active: true,
                default: $this->config->defaultLocale === $config['code'],
            );

            $locales->put($locale->code, $locale);
        }

        return $locales;
    }
}
