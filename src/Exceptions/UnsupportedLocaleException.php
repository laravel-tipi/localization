<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

use RuntimeException;

final class UnsupportedLocaleException extends RuntimeException implements LocalizationException
{
    private function __construct(
        public readonly string $locale,
        public readonly ?string $context,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function inactive(string $code): self
    {
        return new self($code, null, "Locale [{$code}] is not active.");
    }

    public static function notInContext(string $code, string $context): self
    {
        return new self(
            $code,
            $context,
            "Locale [{$code}] is not supported by the [{$context}] locale context.",
        );
    }
}
