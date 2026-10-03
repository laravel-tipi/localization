<?php

declare(strict_types=1);

namespace Tipi\Localization;

use Illuminate\Support\ServiceProvider;

final class LocalizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(LocaleResolver::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'tipi-localization');
    }
}
