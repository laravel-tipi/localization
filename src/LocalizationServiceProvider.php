<?php

declare(strict_types=1);

namespace Tipi\Localization;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Contracts\LocaleContextRepository;
use Tipi\Localization\Contracts\LocaleRepository;
use Tipi\Localization\Enums\StorageDriver;
use Tipi\Localization\Http\Middleware\UseLocaleContext;
use Tipi\Localization\Repositories\ConfigLocaleContextRepository;
use Tipi\Localization\Repositories\ConfigLocaleRepository;
use Tipi\Localization\Repositories\DatabaseLocaleContextRepository;
use Tipi\Localization\Repositories\DatabaseLocaleRepository;
use Tipi\Localization\Routing\LocalizedRoutes;
use Tipi\Localization\Routing\LocalizedUrlGenerator;

final class LocalizationServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('localization')
            ->hasConfigFile()
            ->hasRoute('localization')
            ->hasMigrations([
                'create_locales_table',
                'create_locale_contexts_tables',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(
            LocalizationConfig::class,
            fn (Application $app): LocalizationConfig => LocalizationConfig::fromArray(
                (array) $app['config']->get('localization', []),
            ),
        );

        $this->app->bind(
            LocaleRepository::class,
            fn (Application $app): LocaleRepository => match ($app->make(LocalizationConfig::class)->localesDriver) {
                StorageDriver::Config => $app->make(ConfigLocaleRepository::class),
                StorageDriver::Database => $app->make(DatabaseLocaleRepository::class),
            },
        );

        $this->app->bind(
            LocaleContextRepository::class,
            fn (Application $app): LocaleContextRepository => match ($app->make(LocalizationConfig::class)->contextsDriver) {
                StorageDriver::Config => $app->make(ConfigLocaleContextRepository::class),
                StorageDriver::Database => $app->make(DatabaseLocaleContextRepository::class),
            },
        );

        // Per request / per queued job: reset by Laravel and Octane.
        $this->app->scoped(LocaleRegistry::class);
        $this->app->scoped(LocaleContexts::class);
        $this->app->scoped(Localization::class);
    }

    public function packageBooted(): void
    {
        $this->app->make(Router::class)->aliasMiddleware('localization.context', UseLocaleContext::class);

        // route('wines.show') for logical localized names. Laravel consults
        // this resolver only when no route has the exact name.
        $this->callAfterResolving('url', function (UrlGenerator $url, Application $app): void {
            $url->resolveMissingNamedRoutesUsing(
                fn (mixed $name, mixed $parameters, mixed $absolute): ?string => is_string($name)
                    ? $app->make(LocalizedUrlGenerator::class)->resolveMissing($name, $parameters, (bool) $absolute)
                    : null,
            );
        });

        // Every route file and provider has registered its routes by now,
        // so this also catches regular routes added after
        // Localization::routes(). route:cache boots a fresh application,
        // so a collision fails the cache build; a cached application is
        // not checked again.
        $this->app->booted(function (LaravelApplication $app): void {
            if (! $app->routesAreCached()) {
                $app->make(LocalizedRoutes::class)->validate();
            }
        });

        $config = $this->app->make(LocalizationConfig::class);

        Gate::policy($config->localeModel, $config->localePolicy);
        Gate::policy($config->contextModel, $config->contextPolicy);
    }
}
