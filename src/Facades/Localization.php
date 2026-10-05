<?php

declare(strict_types=1);

namespace Tipi\Localization\Facades;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Facade;
use Tipi\Localization\Locale;
use Tipi\Localization\LocaleRegistry;

/**
 * @method static void routes(Closure $routes)
 * @method static string route(string $name, array $parameters = [], ?string $locale = null, bool $absolute = true)
 * @method static RedirectResponse redirectToRoute(string $name, array $parameters = [], int $status = 302)
 * @method static RedirectResponse redirectToLocalizedRoute(string $name, array $parameters = [], int $status = 302)
 * @method static string localizedUrl(string $locale, bool $absolute = true)
 * @method static Locale current()
 * @method static string currentCode()
 * @method static LocaleRegistry locales()
 *
 * @see \Tipi\Localization\Localization
 */
final class Localization extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Tipi\Localization\Localization::class;
    }
}
