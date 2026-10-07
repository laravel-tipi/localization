<?php

declare(strict_types=1);

namespace Tipi\Localization;

use Closure;
use Illuminate\Http\RedirectResponse;
use Tipi\Localization\Actions\RememberCurrentLocale;
use Tipi\Localization\Actions\SetCurrentLocale;
use Tipi\Localization\Routing\LocalizedRouteRegistrar;
use Tipi\Localization\Routing\LocalizedUrlGenerator;
use Tipi\Support\Locale;

final readonly class Localization
{
    public function __construct(
        private LocalizedRouteRegistrar $routes,
        private LocalizedUrlGenerator $urls,
        private LocaleResolver $resolver,
        private LocaleRegistry $locales,
        private LocaleNegotiator $negotiator,
        private SetCurrentLocale $setCurrentLocale,
        private RememberCurrentLocale $rememberCurrentLocale,
    ) {}

    public function routes(Closure $routes): void
    {
        $this->routes->routes($routes);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function route(
        string $name,
        array $parameters = [],
        ?string $locale = null,
        bool $absolute = true,
    ): string {
        return $this->urls->route(
            name: $name,
            parameters: $parameters,
            locale: $locale,
            absolute: $absolute,
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function redirectToRoute(
        string $name,
        array $parameters = [],
        int $status = 302,
    ): RedirectResponse {
        return redirect()->to(
            $this->urls->route(
                name: $name,
                parameters: $parameters,
            ),
            status: $status,
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function redirectToLocalizedRoute(
        string $name,
        array $parameters = [],
        int $status = 302,
    ): RedirectResponse {
        $locale = $this->negotiator->negotiate(
            request(),
        );

        return redirect()->to(
            $this->urls->route(
                name: $name,
                parameters: $parameters,
                locale: $locale->code,
            ),
            status: $status,
        );
    }

    public function current(): Locale
    {
        return $this->resolver->current();
    }

    public function currentCode(): string
    {
        return $this->resolver->currentCode();
    }

    public function locales(): LocaleRegistry
    {
        return $this->locales;
    }

    public function setCurrentLocale(string|Locale $locale): Locale
    {
        return $this->setCurrentLocale->execute($locale);
    }

    public function selectLocale(string|Locale $locale): Locale
    {
        $locale = $this->setCurrentLocale->execute($locale);

        $this->rememberCurrentLocale->execute(request());

        return $locale;
    }
}
