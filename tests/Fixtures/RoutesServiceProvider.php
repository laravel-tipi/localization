<?php

declare(strict_types=1);

namespace Tipi\Localization\Tests\Fixtures;

use Closure;
use Illuminate\Support\ServiceProvider;

/**
 * Registers routes while the application boots, like an application's
 * route files or a package provider would.
 */
final class RoutesServiceProvider extends ServiceProvider
{
    public static ?Closure $routes = null;

    public function boot(): void
    {
        if (self::$routes !== null) {
            (self::$routes)();
        }
    }
}
