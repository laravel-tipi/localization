<?php

declare(strict_types=1);

namespace Tipi\Localization\Config;

final readonly class LocalizationConfig
{
    public function __construct(
        public bool $hideDefaultLocale,
        public bool $negotiateRootLocale,
        public string $negotiatedRootRouteName,
        public string $localeCookie,
        public int $localeCookieMinutes,
    ) {}
}
