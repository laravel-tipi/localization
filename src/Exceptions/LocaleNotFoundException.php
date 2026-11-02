<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

use RuntimeException;

final class LocaleNotFoundException extends RuntimeException implements LocalizationException
{
    public function __construct(
        public readonly string $locale,
    ) {
        parent::__construct("Locale [{$locale}] does not exist.");
    }
}
