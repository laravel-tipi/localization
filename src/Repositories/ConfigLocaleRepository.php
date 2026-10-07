<?php

declare(strict_types=1);

namespace Tipi\Localization\Repositories;

use Illuminate\Support\Collection;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Contracts\LocaleRepository;
use Tipi\Localization\Exceptions\DefaultLocaleNotConfiguredException;
use Tipi\Localization\Exceptions\InvalidLocaleConfigurationException;
use Tipi\Localization\Exceptions\LocalesNotDefinedException;
use Tipi\Localization\Support\LocaleCode;
use Tipi\Support\Enums\TextDirection;
use Tipi\Support\Locale;

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
        $this->validate();

        $locales = new Collection;

        foreach ($this->config->locales as $config) {

            $locale = $this->makeLocale($config);
            $locales->put($locale->code, $locale);
        }

        return $locales;
    }

    private function validate(): void
    {
        if ($this->config->locales === []) {
            throw new LocalesNotDefinedException;
        }

        if ($this->config->defaultLocale === null) {
            throw new DefaultLocaleNotConfiguredException;
        }

        $codes = [];

        foreach ($this->config->locales as $index => $locale) {
            if (! is_array($locale)) {
                throw new InvalidLocaleConfigurationException(
                    "Locale at index $index must be an array.",
                );
            }

            $this->validateLocale($locale, $index);

            $code = $locale['code'];

            if (in_array($code, $codes, true)) {
                throw new InvalidLocaleConfigurationException(
                    "Locale [$code] is defined more than once.",
                );
            }

            $codes[] = $code;
        }

        if (! in_array($this->config->defaultLocale, $codes, true)) {
            throw new InvalidLocaleConfigurationException(
                "Default locale [{$this->config->defaultLocale}] is not defined.",
            );
        }
    }

    /**
     * @param  array<string, mixed>  $locale
     */
    private function validateLocale(array $locale, int $index): void
    {
        foreach (['code', 'name', 'native_name'] as $key) {
            if (
                ! isset($locale[$key])
                || ! is_string($locale[$key])
                || trim($locale[$key]) === ''
            ) {
                throw new InvalidLocaleConfigurationException(
                    "Locale at index $index must contain a non-empty [$key] string.",
                );
            }
        }

        if (! LocaleCode::isValid($locale['code'])) {
            throw new InvalidLocaleConfigurationException(
                "Locale code [{$locale['code']}] is invalid.",
            );
        }

        if (
            isset($locale['country_code'])
            && (
                ! is_string($locale['country_code'])
                || preg_match('/^[A-Z]{2}$/D', $locale['country_code']) !== 1
            )
        ) {
            throw new InvalidLocaleConfigurationException(
                "Country code for locale [{$locale['code']}] must be a two-letter uppercase country code.",
            );
        }

        if (
            isset($locale['text_direction'])
            && (
                ! is_string($locale['text_direction'])
                || TextDirection::tryFrom($locale['text_direction']) === null
            )
        ) {
            throw new InvalidLocaleConfigurationException(
                "Text direction for locale [{$locale['code']}] must be [ltr] or [rtl].",
            );
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function makeLocale(array $config): Locale
    {
        return new Locale(
            code: $config['code'],
            name: $config['name'],
            nativeName: $config['native_name'],
            countryCode: $config['country_code'] ?? null,
            textDirection: TextDirection::from(
                $config['text_direction'] ?? TextDirection::Ltr->value,
            ),
            active: true,
            default: $this->config->defaultLocale === $config['code'],
        );
    }
}
