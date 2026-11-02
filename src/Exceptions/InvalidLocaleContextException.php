<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

use RuntimeException;

/**
 * A locale context definition violates one of the context invariants.
 */
final class InvalidLocaleContextException extends RuntimeException implements LocalizationException
{
    public function __construct(
        public readonly string $context,
        string $reason,
    ) {
        parent::__construct("Locale context [{$context}] is invalid: {$reason}");
    }
}
