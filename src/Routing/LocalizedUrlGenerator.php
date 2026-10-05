<?php

declare(strict_types=1);

namespace Tipi\Localization\Routing;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Route;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Exceptions\CurrentRouteCannotBeLocalizedException;
use Tipi\Localization\LocaleRegistry;
use Tipi\Localization\LocaleResolver;

final readonly class LocalizedUrlGenerator
{
    public function __construct(
        private UrlGenerator $url,
        private LocaleRegistry $locales,
        private LocaleResolver $resolver,
        private LocalizationConfig $config,
    ) {}

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function route(
        string $name,
        array $parameters = [],
        ?string $locale = null,
        bool $absolute = true,
    ): string {
        $locale = $locale === null
            ? $this->resolver->current()
            : $this->locales->supportedLocale($locale);

        if ($this->config->hideDefaultLocale && $locale->isDefault()) {
            return $this->url->route(
                "__localized.default.$name",
                $parameters,
                $absolute,
            );
        }

        return $this->url->route(
            "__localized.locale.$name",
            [
                'locale' => $locale->code,
                ...$parameters,
            ],
            $absolute,
        );
    }

    public function localizedUrl(
        string $locale,
        bool $absolute = true,
    ): string {
        $route = request()->route();

        if (! $route instanceof Route) {
            throw CurrentRouteCannotBeLocalizedException::routeNotFound();
        }

        $name = $route->getName();

        if ($name === null) {
            throw CurrentRouteCannotBeLocalizedException::routeNotNamed();
        }

        $name = match (true) {
            str_starts_with($name, '__localized.default.') => substr(
                $name,
                strlen('__localized.default.'),
            ),

            str_starts_with($name, '__localized.locale.') => substr(
                $name,
                strlen('__localized.locale.'),
            ),

            default => throw CurrentRouteCannotBeLocalizedException::routeNotLocalized(),
        };

        $parameters = $route->parameters();

        unset($parameters['locale']);

        return $this->route(
            name: $name,
            parameters: [
                ...request()->query(),
                ...$parameters,
            ],
            locale: $locale,
            absolute: $absolute,
        );
    }
}
