<?php

declare(strict_types=1);

namespace Tipi\Localization;

use Tipi\Support\Locale;

/**
 * One entry of a language switcher.
 */
final readonly class LocaleLink
{
    public function __construct(
        public Locale $locale,
        public string $url,
        public bool $current,
    ) {}
}
