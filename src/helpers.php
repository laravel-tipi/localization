<?php

declare(strict_types=1);

use Tipi\Localization\Facades\Localization;

if (! function_exists('localized_route')) {
    /**
     * @param  array<string, mixed>  $parameters
     */
    function localized_route(
        string $name,
        array $parameters = [],
        ?string $locale = null,
        bool $absolute = true,
    ): string {
        return Localization::route(
            name: $name,
            parameters: $parameters,
            locale: $locale,
            absolute: $absolute,
        );
    }
}
if (! function_exists('localized_url')) {
    function localized_url(
        string $locale,
        bool $absolute = true,
    ): string {
        return Localization::localizedUrl(
            locale: $locale,
            absolute: $absolute,
        );
    }
}
