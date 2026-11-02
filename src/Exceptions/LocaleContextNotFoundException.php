<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

use RuntimeException;

final class LocaleContextNotFoundException extends RuntimeException implements LocalizationException
{
    public function __construct(
        public readonly string $context,
    ) {
        parent::__construct("Locale context [{$context}] does not exist.");
    }
}
