<?php

declare(strict_types=1);

namespace Tipi\Localization\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\LocaleRegistry;
use Tipi\Localization\Routing\LocalizedUrlGenerator;

final readonly class RedirectDefaultLocale
{
    public function __construct(
        private LocaleRegistry $locales,
        private LocalizationConfig $config,
        private LocalizedUrlGenerator $urls,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldRedirect($request)) {
            return $next($request);
        }

        return redirect()->to(
            $this->redirectUrl($request),
        );
    }

    private function shouldRedirect(Request $request): bool
    {
        if (! $this->config->hideDefaultLocale) {
            return false;
        }

        $code = $request->route('locale');

        if (! is_string($code)) {
            return false;
        }

        return $code === $this->locales->defaultCode();
    }

    private function redirectUrl(Request $request): string
    {
        $route = $request->route();

        $name = str($route->getName())
            ->after('__localized.locale.')
            ->toString();

        $parameters = $route->parameters();

        unset($parameters['locale']);

        $parameters = [
            ...$request->query(),
            ...$parameters,
        ];

        return $this->urls->route(
            name: $name,
            parameters: $parameters,
            locale: $this->locales->defaultCode(),
        );
    }
}
