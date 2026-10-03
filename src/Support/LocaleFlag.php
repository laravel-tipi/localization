<?php

declare(strict_types=1);

namespace Tipi\Localization\Support;

final class LocaleFlag
{
    public static function country(string $locale): string
    {
        return match ($locale) {
            'ka' => 'ge',
            'en' => 'gb',
            default => 'un',
        };
    }
}
