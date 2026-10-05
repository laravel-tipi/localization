<?php

declare(strict_types=1);

namespace Tipi\Localization;

use Illuminate\Support\ServiceProvider;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Contracts\LocaleRepository;
use Tipi\Localization\Enums\LocaleDriver;
use Tipi\Localization\Repositories\ConfigLocaleRepository;
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
                localesDriver: LocaleDriver::from(
                    config(
                        'localization.locales_driver',
                        'database'
                    ),
                ),
                locales: (array) config('localization.locales', []),
                defaultLocale: config('localization.default_locale'),
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
            fn ($app): LocaleRepository => match (
                $app->make(LocalizationConfig::class)->localesDriver
            ) {
                LocaleDriver::Database => $app->make(DatabaseLocaleRepository::class),
                LocaleDriver::Config => $app->make(ConfigLocaleRepository::class),
            },
        );

        $this->app->scoped(LocaleRegistry::class);
        $this->app->scoped(LocaleResolver::class);
        $this->app->scoped(LocaleNegotiator::class);
        $this->app->scoped(Localization::class);
    }

    public function boot(): void
    {
        if (resolve(LocalizationConfig::class)->localesDriver === LocaleDriver::Database) {
            $this->loadMigrationsFrom(
                __DIR__.'/../database/migrations',
            );
        }

        $this->publishes([
            __DIR__.'/../config/localization.php' => config_path('localization.php'),
        ], 'localization-config');
    }
}
