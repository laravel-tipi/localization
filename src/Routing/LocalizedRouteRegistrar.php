<?php

declare(strict_types=1);

namespace Tipi\Localization\Routing;

use Closure;
use Illuminate\Routing\Router;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Http\Middleware\NegotiateRootLocale;
use Tipi\Localization\Http\Middleware\RedirectDefaultLocale;
use Tipi\Localization\Http\Middleware\RememberLocale;
use Tipi\Localization\Http\Middleware\SetLocale;
use Tipi\Localization\Support\LocaleCode;

final readonly class LocalizedRouteRegistrar
{
    public function __construct(
        private Router $router,
        private LocalizationConfig $config,
    ) {}

    public function routes(Closure $routes): void
    {
        if ($this->config->hideDefaultLocale) {
            $this->router->group([
                'as' => '__localized.default.',
                'middleware' => [
                    SetLocale::class,
                    NegotiateRootLocale::class,
                    RememberLocale::class,
                ],
            ], $routes);
        }

        $this->router
            ->prefix('{locale}')
            ->where([
                'locale' => LocaleCode::ROUTE_PATTERN,
            ])
            ->name('__localized.locale.')
            ->middleware([
                SetLocale::class,
                RedirectDefaultLocale::class,
                RememberLocale::class,
            ])
            ->group($routes);
    }
}
