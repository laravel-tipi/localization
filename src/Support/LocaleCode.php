<?php

declare(strict_types=1);

namespace Tipi\Localization\Support;

final class LocaleCode
{
    /**
     * Supported forms: en, eng, en-US, en-001, zh-Hans, zh-Hans-CN.
     */
    public const string PATTERN =
        '[a-z]{2,3}(?:-[A-Z][a-z]{3})?(?:-(?:[A-Z]{2}|[0-9]{3}))?';

    /**
     * The longest code the pattern accepts ("yue-Hant-001").
     */
    public const int MAX_LENGTH = 12;

    public static function isValid(string $code): bool
    {
        return preg_match('/^'.self::PATTERN.'$/D', $code) === 1;
    }
}
