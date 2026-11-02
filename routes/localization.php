<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Http\Controllers\SwitchLocaleController;
use Tipi\Localization\Support\LocaleCode;

$config = app(LocalizationConfig::class);

if ($config->switchRouteEnabled) {
    Route::middleware($config->switchRouteMiddleware)
        ->get($config->switchRouteUri, SwitchLocaleController::class)
        ->where('locale', LocaleCode::PATTERN)
        ->name($config->switchRouteName);
}
