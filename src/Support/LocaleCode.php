<?php

declare(strict_types=1);

namespace Tipi\Localization\Support;

final class LocaleCode
{
    /**
     * Supported forms:
     *
     * en
     * en-US
     * en-001
     * zh-Hans
     * zh-Hans-CN
     */
    public const string PATTERN =
        '[a-z]{2,3}(?:-[A-Z][a-z]{3})?(?:-(?:[A-Z]{2}|[0-9]{3}))?';

    public const string ROUTE_PATTERN = self::PATTERN;

    public static function isValid(string $code): bool
    {
        return preg_match('/^'.self::PATTERN.'$/D', $code) === 1;
    }
}
