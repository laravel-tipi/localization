<?php

declare(strict_types=1);

namespace Tipi\Localization\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\LocaleContexts;
use Tipi\Localization\LocaleNegotiator;
use Tipi\Localization\Routing\LocalizedRouteName;
use Tipi\Localization\Routing\LocalizedUrlGenerator;

/**
 * On the unprefixed root route only: redirects visitors whose remembered
 * or browser locale is not the default. Responses vary on Accept-Language
 * and Cookie so shared caches never serve one visitor's choice to another.
 *
 * @internal
 */
final readonly class NegotiateRootLocale
{
    public function __construct(
        private LocaleContexts $contexts,
        private LocaleNegotiator $negotiator,
        private LocalizedUrlGenerator $urls,
        private LocalizationConfig $config,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (
            ! $this->config->negotiateRootLocale
            || ! $request->isMethodSafe()
            || $request->route()?->getName() !== LocalizedRouteName::unprefixed($this->config->rootRoute)
        ) {
            return $next($request);
        }

        $context = $this->contexts->default();
        $locale = $this->negotiator->negotiate($request, $context);

        $response = $context->isDefault($locale)
            ? $next($request)
            : redirect()->to($this->urls->route($this->config->rootRoute, locale: $locale));

        $response->setVary(['Accept-Language', 'Cookie'], false);

        return $response;
    }
}
