<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

use RuntimeException;

final class CurrentRouteCannotBeLocalizedException extends RuntimeException
{
    public static function routeNotFound(): self
    {
        return new self(
            'The current request does not have a route.',
        );
    }

    public static function routeNotNamed(): self
    {
        return new self(
            'The current route is not named.',
        );
    }

    public static function routeNotLocalized(): self
    {
        return new self(
            'The current route is not a localized route.',
        );
    }
}
