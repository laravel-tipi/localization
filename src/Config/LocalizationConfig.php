<?php

declare(strict_types=1);

namespace Tipi\Localization\Config;

use Tipi\Localization\Enums\LocaleDriver;

final readonly class LocalizationConfig
{
    /**
     * @param array<int, array{
     *     code: string,
     *     name: string,
     *     native_name: string,
     *     country_code?: string|null,
     *     text_direction?: string,
     * }> $locales
     */
    public function __construct(
        public LocaleDriver $localesDriver,
        public array $locales,
        public ?string $defaultLocale,
        public bool $hideDefaultLocale,
        public bool $negotiateRootLocale,
        public string $negotiatedRootRouteName,
        public string $localeCookie,
        public int $localeCookieMinutes,
    ) {}
}
