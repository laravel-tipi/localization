<?php

declare(strict_types=1);

namespace Tipi\Localization\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\LocaleNegotiator;
use Tipi\Localization\Routing\LocalizedUrlGenerator;

final readonly class NegotiateRootLocale
{
    public function __construct(
        private LocaleNegotiator $negotiator,
        private LocalizedUrlGenerator $urls,
        private LocalizationConfig $config,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldNegotiate($request)) {
            return $next($request);
        }

        $locale = $this->negotiator->negotiate($request);

        if ($locale->isDefault()) {
            return $next($request);
        }

        return redirect()->to(
            $this->urls->route(
                name: $this->config->negotiatedRootRouteName,
                locale: $locale->code,
            ),
        );
    }

    private function shouldNegotiate(Request $request): bool
    {
        if (! $this->config->negotiateRootLocale) {
            return false;
        }

        return $request->route()?->getName()
            === "__localized.default.{$this->config->negotiatedRootRouteName}";
    }
}
