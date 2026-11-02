<?php

declare(strict_types=1);

namespace Tipi\Localization\Routing;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Exceptions\InvalidLocalizedRouteException;
use Tipi\Localization\LocaleContext;
use Tipi\Localization\LocaleContexts;
use Tipi\Support\Locale;

/**
 * URLs for localized routes. Localized routes belong to the default
 * context; its default locale is the one hidden from URLs.
 *
 * @internal Use the Localization facade.
 */
final readonly class LocalizedUrlGenerator
{
    public function __construct(
        private UrlGenerator $url,
        private LocaleContexts $contexts,
        private LocalizationConfig $config,
        private Application $app,
        private Router $router,
    ) {}

    /**
     * Whether a logical localized route name exists.
     */
    public function has(string $name): bool
    {
        return $this->router->has(LocalizedRouteName::prefixed($name));
    }

    /**
     * Laravel's missing-named-route hook: makes route('wines.show', [...])
     * work for logical localized names. Laravel calls it only after its own
     * lookup failed, so regular routes are never affected. A "locale"
     * parameter (code or Locale) selects the locale; otherwise the current
     * locale is used.
     */
    public function resolveMissing(string $name, mixed $parameters, bool $absolute): ?string
    {
        if (! $this->has($name)) {
            return null;
        }

        $parameters = Arr::wrap($parameters);
        $locale = Arr::pull($parameters, 'locale');

        if ($locale !== null && ! is_string($locale) && ! $locale instanceof Locale) {
            throw InvalidLocalizedRouteException::invalidLocaleParameter($locale);
        }

        return $this->route($name, $parameters, $locale, $absolute);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function route(
        string $name,
        array $parameters = [],
        string|Locale|null $locale = null,
        bool $absolute = true,
    ): string {
        $context = $this->contexts->default();
        $locale = $this->localeFor($context, $locale);

        if ($this->config->hideDefaultLocale && $context->isDefault($locale)) {
            return $this->url->route(LocalizedRouteName::unprefixed($name), $parameters, $absolute);
        }

        return $this->url->route(
            LocalizedRouteName::prefixed($name),
            ['locale' => $locale->code, ...$parameters],
            $absolute,
        );
    }

    /**
     * The current page in another locale, or null when the current request
     * is not on a localized route (error pages, admin pages, console).
     */
    public function current(Request $request, string|Locale $locale, bool $absolute = true): ?string
    {
        $route = $request->route();

        if (! $route instanceof Route) {
            return null;
        }

        $name = LocalizedRouteName::logical($route->getName());

        if ($name === null) {
            return null;
        }

        $parameters = $route->parameters();

        unset($parameters['locale']);

        return $this->route(
            name: $name,
            parameters: [...$request->query(), ...$parameters],
            locale: $locale,
            absolute: $absolute,
        );
    }

    private function localeFor(LocaleContext $context, string|Locale|null $locale): Locale
    {
        if ($locale !== null) {
            return $context->get($locale);
        }

        return $context->find($this->app->getLocale()) ?? $context->default();
    }
}
