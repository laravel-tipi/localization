<?php

declare(strict_types=1);

namespace Tipi\Localization\Support;

final class ContextName
{
    public const string PATTERN = '[a-z][a-z0-9_-]*';

    public const int MAX_LENGTH = 64;

    public static function isValid(string $name): bool
    {
        return strlen($name) <= self::MAX_LENGTH
            && preg_match('/^'.self::PATTERN.'$/D', $name) === 1;
    }
}
