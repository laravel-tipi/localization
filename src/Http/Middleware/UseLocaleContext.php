<?php

declare(strict_types=1);

namespace Tipi\Localization\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tipi\Localization\LocaleContexts;
use Tipi\Localization\LocaleNegotiator;

/**
 * For non-localized areas (admin panels, dashboards, APIs): makes the
 * given context current and sets the application locale to the
 * negotiated one (remembered preference, then Accept-Language, then the
 * context default).
 *
 *     Route::middleware(UseLocaleContext::with('admin'))
 *     Route::middleware('localization.context:admin')
 */
final readonly class UseLocaleContext
{
    public function __construct(
        private LocaleContexts $contexts,
        private LocaleNegotiator $negotiator,
        private Application $app,
    ) {}

    public static function with(string $context): string
    {
        return self::class.':'.$context;
    }

    public function handle(Request $request, Closure $next, ?string $context = null): Response
    {
        $context = $this->contexts->setCurrent($context ?? $this->contexts->default()->name);

        $this->app->setLocale($this->negotiator->negotiate($request, $context)->code);

        $response = $next($request);

        $response->setVary(['Accept-Language', 'Cookie'], false);

        return $response;
    }
}
