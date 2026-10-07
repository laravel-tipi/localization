<?php

declare(strict_types=1);

namespace Tipi\Localization\Support;

final class CountryCode
{
    public const string PATTERN = '[A-Z]{2}';

    public static function isValid(string $code): bool
    {
        return preg_match('/^'.self::PATTERN.'$/D', $code) === 1;
    }
}