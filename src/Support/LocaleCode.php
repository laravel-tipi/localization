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
     * zh-Hans
     * zh-Hans-CN
     */
    public const string ROUTE_PATTERN =
        '[a-z]{2,3}(?:-[A-Z][a-z]{3})?(?:-(?:[A-Z]{2}|[0-9]{3}))?';
}
