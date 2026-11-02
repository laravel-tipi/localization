<?php

declare(strict_types=1);

namespace Tipi\Localization;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Exceptions\InvalidLocaleConfigurationException;
use Tipi\Localization\Exceptions\UnsupportedLocaleException;
use Tipi\Localization\Routing\LocalizedRouteName;
use Tipi\Localization\Routing\LocalizedRouteRegistrar;
use Tipi\Localization\Routing\LocalizedUrlGenerator;
use Tipi\Support\Locale;

/**
 * Root of the Localization facade.
 *
 * Terms: "active" locales exist globally (LocaleRegistry); "supported"
 * locales are the active locales a context offers (LocaleContext).
 */
final readonly class Localization
{
    public function __construct(
        private LocaleRegistry $locales,
        private LocaleContexts $contexts,
        private LocalePreferences $preferences,
        private LocaleNegotiator $negotiator,
        private LocalizedRouteRegistrar $registrar,
        private LocalizedUrlGenerator $urls,
        private UrlGenerator $url,
        private LocalizationConfig $config,
        private Application $app,
    ) {}

    /*
     | Locales and contexts
     */

    /**
     * The global registry: all locales, active locales, global default.
     */
    public function locales(): LocaleRegistry
    {
        return $this->locales;
    }

    public function contexts(): LocaleContexts
    {
        return $this->contexts;
    }

    /**
     * The named context, or the current one.
     */
    public function context(?string $name = null): LocaleContext
    {
        return $name === null ? $this->contexts->current() : $this->contexts->get($name);
    }

    /*
     | Current locale
     */

    /**
     * The locale of the application right now (App::getLocale()), so it
     * follows App::setLocale() and App::withLocale(). Falls back to the
     * current context's default when the application locale is not an
     * active locale.
     */
    public function current(): Locale
    {
        $locale = $this->locales->find($this->app->getLocale());

        return $locale !== null && $locale->isActive()
            ? $locale
            : $this->contexts->current()->default();
    }

    public function currentCode(): string
    {
        return $this->current()->code;
    }

    /*
     | Explicit selection and remembered preference
     */

    /**
     * Explicitly select a locale in a context (the current one by default):
     * it is remembered for future requests and, when the context is the
     * current one, becomes the application locale immediately.
     *
     * @throws UnsupportedLocaleException
     */
    public function select(string|Locale $locale, ?string $context = null): Locale
    {
        $context = $this->context($context);
        $locale = $context->get($locale);

        $this->preferences->remember($context, $locale, $this->request());

        if ($context->name === $this->contexts->current()->name) {
            $this->app->setLocale($locale->code);
        }

        return $locale;
    }

    /**
     * The remembered (explicitly selected) locale, if still supported.
     */
    public function preference(?string $context = null): ?Locale
    {
        return $this->preferences->get($this->context($context), $this->request());
    }

    public function forgetPreference(?string $context = null): void
    {
        $this->preferences->forget($this->context($context), $this->request());
    }

    /**
     * Preference, then Accept-Language, then the context default.
     */
    public function negotiate(?string $context = null, ?Request $request = null): Locale
    {
        return $this->negotiator->negotiate($request ?? $this->request(), $this->context($context));
    }

    /*
     | Localized routing
     */

    public function routes(Closure $routes): void
    {
        $this->registrar->register($routes);
    }

    /**
     * URL of a localized route. Equivalent to Laravel's
     * route($name, [...$parameters, 'locale' => $locale]). Without a
     * locale, the current locale is used when the default context
     * supports it, otherwise its default.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function route(
        string $name,
        array $parameters = [],
        string|Locale|null $locale = null,
        bool $absolute = true,
    ): string {
        return $this->urls->route($name, $parameters, $locale, $absolute);
    }

    /**
     * Route::has() for logical localized route names.
     */
    public function hasRoute(string $name): bool
    {
        return $this->urls->has($name);
    }

    /**
     * The current page in another locale, keeping route parameters and the
     * query string. Null when the current route is not a localized route.
     */
    public function currentUrl(string|Locale $locale, bool $absolute = true): ?string
    {
        return $this->urls->current($this->request(), $locale, $absolute);
    }

    /**
     * URL that records the choice and returns to the current page in that
     * locale (or to the current path on non-localized pages).
     */
    public function switchUrl(string|Locale $locale, ?string $context = null): string
    {
        if (! $this->config->switchRouteEnabled) {
            throw new InvalidLocaleConfigurationException(
                'the locale switch route is disabled ([switch_route.enabled] is false).',
            );
        }

        $context = $this->context($context);
        $locale = $context->get($locale);

        $redirect = $context->name === $this->contexts->default()->name
            ? $this->currentUrl($locale, absolute: false)
            : null;

        return $this->url->route($this->config->switchRouteName, array_filter([
            'locale' => $locale->code,
            'redirect' => $redirect ?? $this->request()->getRequestUri(),
            'context' => $context->name === $this->contexts->default()->name ? null : $context->name,
        ]));
    }

    /**
     * Language switcher entries for a context (the current one by default).
     *
     * @return Collection<int, LocaleLink>
     */
    public function switcher(?string $context = null): Collection
    {
        $context = $this->context($context);
        $current = $this->currentCode();

        return $context->supported()->values()->map(
            fn (Locale $locale): LocaleLink => new LocaleLink(
                locale: $locale,
                url: $this->switchUrl($locale, $context->name),
                current: $locale->code === $current,
            ),
        );
    }

    /**
     * hreflang => absolute URL of the current page for every supported
     * locale of the default context, plus "x-default". Empty when the
     * current route is not localized.
     *
     * @return Collection<string, string>
     */
    public function alternates(): Collection
    {
        $context = $this->contexts->default();

        $links = $context->supported()->mapWithKeys(
            fn (Locale $locale): array => [$locale->code => $this->currentUrl($locale)],
        )->filter();

        if ($links->isEmpty()) {
            return $links;
        }

        return $links->put('x-default', $links->get($context->defaultCode()));
    }

    /**
     * The logical name of the current localized route ("wines.show").
     */
    public function currentRouteName(): ?string
    {
        $route = $this->request()->route();

        return $route instanceof Route ? LocalizedRouteName::logical($route->getName()) : null;
    }

    /**
     * request()->routeIs() for logical localized route names.
     */
    public function routeIs(string ...$patterns): bool
    {
        $name = $this->currentRouteName();

        return $name !== null && Str::is($patterns, $name);
    }

    private function request(): Request
    {
        return $this->app->make('request');
    }
}
