<?php

declare(strict_types=1);

namespace Tipi\Localization\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tipi\Localization\Actions\SetCurrentLocale;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Exceptions\LocaleNotFoundException;
use Tipi\Localization\Exceptions\UnsupportedLocaleException;
use Tipi\Localization\LocaleRegistry;
use Tipi\Support\Locale;

final readonly class SetRememberedLocale
{
    public function __construct(
        private LocaleRegistry $locales,
        private LocalizationConfig $config,
        private SetCurrentLocale $setCurrentLocale,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->setCurrentLocale->execute(
            $this->resolve($request),
        );

        return $next($request);
    }

    private function resolve(Request $request): Locale
    {
        $code = $request->session()->get(
            $this->config->localeSession,
        );

        if (is_string($code)) {
            try {
                return $this->locales->supportedLocale($code);
            } catch (LocaleNotFoundException|UnsupportedLocaleException) {
                //
            }
        }

        $code = $request->cookie(
            $this->config->localeCookie,
        );

        if (is_string($code)) {
            try {
                return $this->locales->supportedLocale($code);
            } catch (LocaleNotFoundException|UnsupportedLocaleException) {
                //
            }
        }

        return $this->locales->default();
    }
}