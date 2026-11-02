<?php

declare(strict_types=1);

namespace Tipi\Localization\Routing;

use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollectionInterface;
use Illuminate\Routing\Router;
use Tipi\Localization\Exceptions\InvalidLocalizedRouteException;

/**
 * Checks the application's localized routes as a whole, and reads what
 * the registrar stored on them. Reserved segments are route metadata, so
 * they are available with cached routes too.
 *
 * @internal
 */
final readonly class LocalizedRoutes
{
    public const string RESERVED_METADATA = 'tipi_localization.reserved';

    public function __construct(
        private Router $router,
    ) {}

    /**
     * Every logical localized name must be unique and must not be used by
     * a regular route. Laravel resolves a regular name before asking the
     * package, so a collision would silently generate the wrong URL.
     *
     * Runs at registration and again once the application has booted, so
     * routes registered after Localization::routes() are checked too.
     *
     * @throws InvalidLocalizedRouteException
     */
    public function validate(): void
    {
        self::ensureUniqueNames($this->router->getRoutes());
    }

    /**
     * @throws InvalidLocalizedRouteException
     */
    public static function ensureUniqueNames(RouteCollectionInterface $routes): void
    {
        $localized = [];

        foreach ($routes->getRoutes() as $route) {
            $name = (string) $route->getName();
            $logical = LocalizedRouteName::logical($name);

            if ($logical === null) {
                continue;
            }

            if (isset($localized[$name])) {
                throw InvalidLocalizedRouteException::duplicate($logical);
            }

            $localized[$name] = $logical;
        }

        // getRoutes() drops a route replaced by a later one with the same
        // method and URI, but the name lookup still resolves its name.
        $names = array_flip([
            ...array_map(fn (Route $route): string => (string) $route->getName(), $routes->getRoutes()),
            ...array_map('strval', array_keys($routes->getRoutesByName())),
        ]);

        foreach ($localized as $logical) {
            if (isset($names[$logical])) {
                throw InvalidLocalizedRouteException::collidesWithRoute($logical);
            }
        }
    }

    /**
     * Static first segments of localized routes that a locale code must
     * not use ("/faq", "/de").
     *
     * @return list<string>
     */
    public function reservedSegments(): array
    {
        $segments = [];

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            foreach ((array) $route->getMetadata(self::RESERVED_METADATA, []) as $segment) {
                $segments[$segment] = $segment;
            }
        }

        return array_values($segments);
    }

    /**
     * @throws InvalidLocalizedRouteException
     */
    public function ensureNotReserved(string $code): void
    {
        if (in_array($code, $this->reservedSegments(), true)) {
            throw InvalidLocalizedRouteException::reservedLocale($code);
        }
    }
}
