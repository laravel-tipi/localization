<?php

declare(strict_types=1);

namespace Tipi\Localization\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tipi\Localization\Exceptions\LocaleNotFoundException;
use Tipi\Localization\Exceptions\UnsupportedLocaleException;
use Tipi\Localization\LocaleRegistry;
use Tipi\Support\Locale;

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

        if (! is_string($code)) {
            abort(404);
        }

        try {
            return $this->locales->supportedLocale($code);
        } catch (LocaleNotFoundException|UnsupportedLocaleException) {
            abort(404);
        }
    }
}
