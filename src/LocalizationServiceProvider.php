<?php

declare(strict_types=1);

namespace Tipi\Localization;

use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Contracts\LocaleRepository;
use Tipi\Localization\Enums\LocaleDriver;
use Tipi\Localization\Repositories\ConfigLocaleRepository;
use Tipi\Localization\Repositories\DatabaseLocaleRepository;

final class LocalizationServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('localization')
            ->hasConfigFile()
            ->hasMigration('create_locales_table');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(LocaleModelResolver::class);

        $this->app->singleton(
            LocalizationConfig::class,
            fn (): LocalizationConfig => new LocalizationConfig(
                localesDriver: LocaleDriver::from(
                    config('localization.locales_driver'),
                ),
                locales: (array) config('localization.locales', []),
                defaultLocale: config('localization.default_locale'),
                hideDefaultLocale: (bool) config(
                    'localization.hide_default_locale',
                ),
                negotiateRootLocale: (bool) config(
                    'localization.negotiate_root_locale',
                ),
                negotiatedRootRouteName: (string) config(
                    'localization.negotiated_root_route_name',
                ),
                localeCookie: (string) config(
                    'localization.cookie.name',
                ),
                localeCookieMinutes: (int) config(
                    'localization.cookie.minutes',
                ),
                localeSession: (string) config(
                    'localization.session.key',
                ),
                localePolicy: (string) config(
                    'localization.locale_policy',
                ),
                localeModel: (string) config(
                    'localization.locale_model',
                ),
                localesTable: (string) config(
                    'localization.locales_table',
                )
            ),
        );

        $this->app->bind(
            LocaleRepository::class,
            fn ($app): LocaleRepository => match (
                $app->make(LocalizationConfig::class)->localesDriver
            ) {
                LocaleDriver::Database => $app->make(
                    DatabaseLocaleRepository::class,
                ),
                LocaleDriver::Config => $app->make(
                    ConfigLocaleRepository::class,
                ),
            },
        );

        $this->app->scoped(LocaleRegistry::class);
        $this->app->scoped(LocaleResolver::class);
        $this->app->scoped(LocaleNegotiator::class);
        $this->app->scoped(Localization::class);
    }

    public function packageBooted(): void
    {
        $config = resolve(LocalizationConfig::class);

        Gate::policy(
            $config->localeModel,
            $config->localePolicy,
        );
    }
}
