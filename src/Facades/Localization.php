<?php

declare(strict_types=1);

namespace Tipi\Localization\Facades;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Tipi\Localization\LocaleContext;
use Tipi\Localization\LocaleContexts;
use Tipi\Localization\LocaleLink;
use Tipi\Localization\LocaleRegistry;
use Tipi\Support\Locale;

/**
 * @method static LocaleRegistry locales()
 * @method static LocaleContexts contexts()
 * @method static LocaleContext context(?string $name = null)
 * @method static Locale current()
 * @method static string currentCode()
 * @method static Locale select(string|Locale $locale, ?string $context = null)
 * @method static Locale|null preference(?string $context = null)
 * @method static void forgetPreference(?string $context = null)
 * @method static Locale negotiate(?string $context = null, ?Request $request = null)
 * @method static void routes(Closure $routes)
 * @method static string route(string $name, array $parameters = [], string|Locale|null $locale = null, bool $absolute = true)
 * @method static bool hasRoute(string $name)
 * @method static string|null currentUrl(string|Locale $locale, bool $absolute = true)
 * @method static string switchUrl(string|Locale $locale, ?string $context = null)
 * @method static Collection<int, LocaleLink> switcher(?string $context = null)
 * @method static Collection<string, string> alternates()
 * @method static string|null currentRouteName()
 * @method static bool routeIs(string ...$patterns)
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
