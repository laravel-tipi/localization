<?php

declare(strict_types=1);

namespace Tipi\Localization;

use Illuminate\Http\Request;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Exceptions\LocaleNotFound;
use Tipi\Localization\Exceptions\UnsupportedLocale;

final readonly class LocaleNegotiator
{
    public function __construct(
        private LocaleRegistry $locales,
        private LocalizationConfig $config,
    ) {}

    public function negotiate(Request $request): Locale
    {
        return $this->fromCookie($request)
            ?? $this->fromBrowser($request)
            ?? $this->locales->default();

        /*$preferred = $request->getPreferredLanguage(
            $this->locales->supportedCodes(),
        );

        if ($preferred !== null) {
            return $this->locales->supportedLocale($preferred);
        }

        return $this->locales->default();*/
    }

    private function fromCookie(Request $request): ?Locale
    {
        $code = $request->cookie(
            $this->config->localeCookie,
        );

        if (! is_string($code)) {
            return null;
        }

        try {
            return $this->locales->supportedLocale($code);
        } catch (LocaleNotFound|UnsupportedLocale) {
            return null;
        }
    }

    private function fromBrowser(Request $request): ?Locale
    {
        $code = $request->getPreferredLanguage(
            $this->locales->supportedCodes(),
        );

        if ($code === null) {
            return null;
        }

        return $this->locales->supportedLocale($code);
    }
}
