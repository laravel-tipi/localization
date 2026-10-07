<?php

declare(strict_types=1);

namespace Tipi\Localization\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\LocaleResolver;

final readonly class RememberLocale
{
    public function __construct(
        private LocaleResolver $resolver,
        private LocalizationConfig $config,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $locale = $this->resolver->currentCode();

        if ($request->hasSession()) {

            $request->session()->put(
                $this->config->localeSession,
                $locale,
            );
        }

        $response->headers->setCookie(
            cookie(
                name: $this->config->localeCookie,
                value: $locale,
                minutes: $this->config->localeCookieMinutes,
            ),
        );

        return $response;
    }
}
