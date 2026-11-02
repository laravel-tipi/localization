<?php

declare(strict_types=1);

namespace Tipi\Localization;

use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;
use Illuminate\Http\Request;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Support\Locale;

/**
 * Stores explicit locale choices: one cookie per context. Only explicit
 * selection writes; visiting a localized URL does not.
 *
 * Cookies are queued, so they are attached by Laravel's
 * AddQueuedCookiesToResponse middleware (part of the "web" group) and
 * encrypted by EncryptCookies like any other cookie.
 */
final readonly class LocalePreferences
{
    public function __construct(
        private LocalizationConfig $config,
        private CookieJar $cookies,
    ) {}

    /**
     * The remembered locale, if it is still supported by the context.
     */
    public function get(LocaleContext $context, Request $request): ?Locale
    {
        $code = $request->cookie($this->cookieName($context));

        return is_string($code) ? $context->find($code) : null;
    }

    public function remember(LocaleContext $context, Locale $locale, Request $request): void
    {
        $context->get($locale);

        $name = $this->cookieName($context);

        $this->cookies->unqueue($name);

        if ($request->cookie($name) === $locale->code) {
            return;
        }

        $this->cookies->queue(
            $this->cookies->make($name, $locale->code, $this->config->preferenceMinutes),
        );
    }

    public function forget(LocaleContext $context, Request $request): void
    {
        $name = $this->cookieName($context);

        $this->cookies->unqueue($name);

        if ($request->cookie($name) !== null) {
            $this->cookies->queue($this->cookies->forget($name));
        }
    }

    public function cookieName(LocaleContext $context): string
    {
        return "{$this->config->preferenceCookie}_{$context->name}";
    }
}
