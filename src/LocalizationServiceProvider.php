<?php

declare(strict_types=1);

namespace Tipi\Localization;

use Illuminate\Support\ServiceProvider;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Contracts\LocaleRepository;
use Tipi\Localization\Repositories\DatabaseLocaleRepository;

final class LocalizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/localization.php',
            'localization',
        );

        $this->app->singleton(
            LocalizationConfig::class,
            fn (): LocalizationConfig => new LocalizationConfig(
                hideDefaultLocale: (bool) config(
                    'localization.hide_default_locale',
                    true
                ),
                negotiateRootLocale: (bool) config(
                    'localization.negotiate_root_locale',
                    true,
                ),
                negotiatedRootRouteName: (string) config(
                    'localization.negotiated_root_route_name',
                    'home',
                ),
                localeCookie: (string) config(
                    'localization.cookie.name',
                    'locale',
                ),
                localeCookieMinutes: (int) config(
                    'localization.cookie.minutes',
                    60 * 24 * 365,
                ),
            ),
        );

        $this->app->bind(
            LocaleRepository::class,
            DatabaseLocaleRepository::class,
        );

        $this->app->scoped(LocaleRegistry::class);
        $this->app->scoped(LocaleResolver::class);
        $this->app->scoped(LocaleNegotiator::class);
        $this->app->scoped(Localization::class);

    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(
            __DIR__.'/../database/migrations',
        );
        $this->loadViewsFrom(
            __DIR__.'/../resources/views',
            'tipi-localization',
        );

        $this->publishes([
            __DIR__.'/../config/localization.php' => config_path('localization.php'),
        ], 'localization-config');
    }
}
