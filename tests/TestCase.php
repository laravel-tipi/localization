<?php

declare(strict_types=1);

namespace Tipi\Localization\Tests;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as Orchestra;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Facades\Localization;
use Tipi\Localization\LocaleContexts;
use Tipi\Localization\LocaleRegistry;
use Tipi\Localization\LocalizationServiceProvider;
use Tipi\Localization\Tests\Fixtures\RoutesServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LocalizationServiceProvider::class, RoutesServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', self::databaseConnection());

        $app['config']->set('localization.locales', [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English'],
            ['code' => 'ka', 'name' => 'Georgian', 'native_name' => 'ქართული'],
            ['code' => 'ru', 'name' => 'Russian', 'native_name' => 'Русский'],
            ['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch'],
        ]);
        $app['config']->set('localization.default_locale', 'en');
    }

    /**
     * SQLite in memory by default. Set LOCALIZATION_TEST_DB to "mysql",
     * "mariadb" or "pgsql" (plus LOCALIZATION_TEST_DB_HOST/PORT/DATABASE/
     * USERNAME/PASSWORD) to run the suite against a real server.
     *
     * @return array<string, mixed>
     */
    private static function databaseConnection(): array
    {
        $driver = getenv('LOCALIZATION_TEST_DB') ?: 'sqlite';

        if ($driver === 'sqlite') {
            return ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        }

        return [
            'driver' => $driver,
            'host' => getenv('LOCALIZATION_TEST_DB_HOST') ?: '127.0.0.1',
            'port' => getenv('LOCALIZATION_TEST_DB_PORT') ?: ($driver === 'pgsql' ? '5432' : '3306'),
            'database' => getenv('LOCALIZATION_TEST_DB_DATABASE') ?: 'tipi',
            'username' => getenv('LOCALIZATION_TEST_DB_USERNAME') ?: ($driver === 'pgsql' ? 'postgres' : 'root'),
            'password' => getenv('LOCALIZATION_TEST_DB_PASSWORD') ?: '',
            'charset' => $driver === 'pgsql' ? 'utf8' : 'utf8mb4',
            'collation' => $driver === 'pgsql' ? null : 'utf8mb4_unicode_ci',
            'prefix' => '',
            'search_path' => 'public',
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        if ((getenv('LOCALIZATION_TEST_DB') ?: 'sqlite') !== 'sqlite') {
            $this->artisan('db:wipe', ['--force' => true]);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Change localization config and rebuild every cached service.
     *
     * @param  array<string, mixed>  $config
     */
    protected function configureLocalization(array $config): void
    {
        foreach ($config as $key => $value) {
            config()->set("localization.{$key}", $value);
        }

        foreach ([LocalizationConfig::class, LocaleRegistry::class, LocaleContexts::class, \Tipi\Localization\Localization::class] as $abstract) {
            $this->app->forgetInstance($abstract);
        }

        Facade::clearResolvedInstances();
    }

    /**
     * Switch to the database locales driver and insert locales in order.
     *
     * @param  list<string>  $codes
     * @param  list<string>  $inactive
     */
    protected function useDatabaseLocales(array $codes = ['en', 'ka', 'ru'], string $default = 'en', array $inactive = []): void
    {
        foreach ($codes as $position => $code) {
            DB::table('locales')->insert([
                'code' => $code,
                'name' => strtoupper($code),
                'native_name' => $code,
                'text_direction' => 'ltr',
                'position' => $position,
                'is_active' => ! in_array($code, $inactive, true),
                'is_default' => $code === $default,
            ]);
        }

        $this->configureLocalization(['locales_driver' => 'database']);
    }

    /**
     * Register localized routes, optionally inside the "web" middleware group.
     */
    protected function localizedRoutes(Closure $routes, bool $web = false): void
    {
        $web
            ? Route::middleware('web')->group(fn () => Localization::routes($routes))
            : Localization::routes($routes);

        app('router')->getRoutes()->refreshNameLookups();
    }
}
