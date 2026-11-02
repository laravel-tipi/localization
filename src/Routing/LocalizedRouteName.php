<?php

declare(strict_types=1);

namespace Tipi\Localization\Routing;

/**
 * Internal route names. Every localized route is registered twice: an
 * unprefixed variant (default locale, when hidden) and a {locale}-prefixed
 * variant. Application code uses the logical name ("wines.show").
 *
 * @internal
 */
final class LocalizedRouteName
{
    public const string UNPREFIXED = '__localized.default.';

    public const string PREFIXED = '__localized.locale.';

    public const string ROOT = '__localized.root';

    public static function unprefixed(string $name): string
    {
        return self::UNPREFIXED.$name;
    }

    public static function prefixed(string $name): string
    {
        return self::PREFIXED.$name;
    }

    /**
     * The logical name of an internal localized route name, or null when
     * the name does not belong to a localized route.
     */
    public static function logical(?string $name): ?string
    {
        foreach ([self::UNPREFIXED, self::PREFIXED] as $prefix) {
            if ($name !== null && str_starts_with($name, $prefix) && strlen($name) > strlen($prefix)) {
                return substr($name, strlen($prefix));
            }
        }

        return null;
    }
}
