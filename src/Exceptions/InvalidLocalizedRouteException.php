<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

use LogicException;

final class InvalidLocalizedRouteException extends LogicException implements LocalizationException
{
    public static function unnamed(string $uri): self
    {
        return new self(
            "Localized route [{$uri}] must be named. Every route registered with "
            .'Localization::routes() needs a name so it can be generated and redirected '
            .'for each locale.',
        );
    }

    public static function duplicate(string $name): self
    {
        return new self("Localized route name [{$name}] is registered more than once.");
    }

    public static function collidesWithRoute(string $name): self
    {
        return new self(
            "Localized route [{$name}] has the same name as a regular route. Laravel would "
            .'always generate the regular route for route(\''.$name.'\'). Rename one of them.',
        );
    }

    public static function reservedLocale(string $code): self
    {
        return new self(
            "Locale [{$code}] collides with the static localized route segment \"/{$code}\": "
            ."URLs starting with \"/{$code}\" must be either that page or the [{$code}] locale. "
            .'Rename the route segment or use a different locale code.',
        );
    }

    public static function invalidLocaleParameter(mixed $value): self
    {
        return new self(
            'The [locale] route parameter must be a locale code or a Locale, got ['.get_debug_type($value).'].',
        );
    }
}
