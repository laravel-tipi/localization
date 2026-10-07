<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\LocaleResolver;

final readonly class RememberCurrentLocale
{
    public function __construct(
        private LocaleResolver $resolver,
        private LocalizationConfig $config,
    ) {}

    public function execute(Request $request): Cookie
    {
        $locale = $this->resolver->currentCode();

        if ($request->hasSession()) {
            $request->session()->put(
                $this->config->localeSession,
                $locale,
            );
        }

        return cookie(
            name: $this->config->localeCookie,
            value: $locale,
            minutes: $this->config->localeCookieMinutes,
        );
    }
}