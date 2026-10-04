<?php

declare(strict_types=1);

namespace Tipi\Localization\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tipi\Localization\Exceptions\LocaleNotFound;
use Tipi\Localization\Exceptions\UnsupportedLocale;
use Tipi\Localization\Locale;
use Tipi\Localization\LocaleRegistry;

final readonly class SetLocale
{
    public function __construct(
        private LocaleRegistry $locales,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        app()->setLocale($locale->code);

        return $next($request);
    }

    private function resolve(Request $request): Locale
    {
        $code = $request->route('locale');

        if ($code === null) {
            return $this->locales->default();
        }

        try {
            return $this->locales->supportedLocale($code);
        } catch (LocaleNotFound|UnsupportedLocale) {
            abort(404);
        }
    }
}
