<?php

declare(strict_types=1);

namespace Tipi\Localization\Routing;

use Closure;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Enums\StorageDriver;
use Tipi\Localization\Exceptions\InvalidLocalizedRouteException;
use Tipi\Localization\Http\Controllers\RootLocaleRedirectController;
use Tipi\Localization\Http\Middleware\NegotiateRootLocale;
use Tipi\Localization\Http\Middleware\RedirectDefaultLocale;
use Tipi\Localization\Http\Middleware\SetLocaleFromRoute;
use Tipi\Localization\Support\LocaleCode;

/**
 * Registers the closure's routes twice:
 *
 * 1. "{locale}/..." first, so a leading "/ka" is never captured by an
 *    unprefixed catch-all such as "/{page}";
 * 2. the unprefixed variant (only when the default locale is hidden).
 *
 * The {locale} pattern is the generic locale-code shape, not a list of
 * known codes, so locales added at runtime work without rebuilding the
 * route cache; SetLocaleFromRoute checks support per request.
 *
 * Static first segments of the localized routes that look like locale
 * codes ("/faq", "/de") are "reserved": they are excluded from the
 * {locale} pattern so they keep reaching their unprefixed route, and a
 * locale with the same code is rejected (see LocalizedRoutes).
 *
 * @internal
 */
final readonly class LocalizedRouteRegistrar
{
    public function __construct(
        private Router $router,
        private LocalizationConfig $config,
    ) {}

    public function register(Closure $routes): void
    {
        $before = count($this->router->getRoutes()->getRoutes());

        $this->router->group([
            'prefix' => '{locale}',
            'as' => LocalizedRouteName::PREFIXED,
            'where' => ['locale' => LocaleCode::PATTERN],
            'middleware' => [RedirectDefaultLocale::class, SetLocaleFromRoute::class],
        ], $routes);

        $prefixed = array_slice($this->router->getRoutes()->getRoutes(), $before);

        $this->ensureNamed($prefixed);
        LocalizedRoutes::ensureUniqueNames($this->router->getRoutes());

        if (! $this->config->hideDefaultLocale) {
            $this->registerRootRedirect($prefixed);

            return;
        }

        $reserved = $this->reservedSegments($prefixed);

        $this->ensureNoReservedLocale($reserved);

        $this->router->group([
            'as' => LocalizedRouteName::UNPREFIXED,
            'middleware' => [SetLocaleFromRoute::class, NegotiateRootLocale::class],
        ], $routes);

        $all = array_slice($this->router->getRoutes()->getRoutes(), $before);

        foreach ($all as $route) {
            $route->metadata([LocalizedRoutes::RESERVED_METADATA => $reserved]);
        }

        if ($reserved !== []) {
            $pattern = '(?!(?:'.implode('|', array_map(fn (string $segment): string => preg_quote($segment, '#'), $reserved)).')(?:/|$))'.LocaleCode::PATTERN;

            foreach ($prefixed as $route) {
                $route->where('locale', $pattern);
            }
        }
    }

    /**
     * @param  list<Route>  $routes
     */
    private function ensureNamed(array $routes): void
    {
        foreach ($routes as $route) {
            if (LocalizedRouteName::logical($route->getName()) === null) {
                throw InvalidLocalizedRouteException::unnamed(
                    preg_replace('#\{locale\}/?#', '', $route->uri(), 1) ?: '/',
                );
            }
        }
    }

    /**
     * @param  list<Route>  $routes
     * @return list<string>
     */
    private function reservedSegments(array $routes): array
    {
        $segments = [];

        foreach ($routes as $route) {
            $parts = explode('/', $route->uri());
            $next = $parts[array_search('{locale}', $parts, true) + 1] ?? '';

            if (! str_contains($next, '{') && LocaleCode::isValid($next)) {
                $segments[$next] = $next;
            }
        }

        return array_values($segments);
    }

    /**
     * With the config driver the codes are known now, so fail at
     * registration (and therefore at route:cache time).
     *
     * @param  list<string>  $reserved
     */
    private function ensureNoReservedLocale(array $reserved): void
    {
        if ($this->config->localesDriver !== StorageDriver::Config) {
            return;
        }

        foreach ($this->config->locales as $locale) {
            if (in_array($locale['code'], $reserved, true)) {
                throw InvalidLocalizedRouteException::reservedLocale($locale['code']);
            }
        }
    }

    /**
     * @param  list<Route>  $prefixed
     */
    private function registerRootRedirect(array $prefixed): void
    {
        $root = LocalizedRouteName::prefixed($this->config->rootRoute);

        foreach ($prefixed as $route) {
            if ($route->getName() === $root) {
                $this->router->get('/', RootLocaleRedirectController::class)->name(LocalizedRouteName::ROOT);

                return;
            }
        }
    }
}
