<?php

declare(strict_types=1);

namespace Tipi\Localization\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\LocaleContexts;
use Tipi\Localization\Routing\LocalizedRouteName;
use Tipi\Localization\Routing\LocalizedUrlGenerator;

/**
 * When the default locale is hidden, permanently redirects GET/HEAD
 * requests for "/{default}/..." to the canonical unprefixed URL. Other
 * methods are served as-is so form submissions are never lost.
 *
 * @internal
 */
final readonly class RedirectDefaultLocale
{
    public function __construct(
        private LocaleContexts $contexts,
        private LocalizationConfig $config,
        private LocalizedUrlGenerator $urls,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (
            ! $this->config->hideDefaultLocale
            || ! $request->isMethodSafe()
            || $request->route('locale') !== $this->contexts->default()->defaultCode()
        ) {
            return $next($request);
        }

        $route = $request->route();
        $name = LocalizedRouteName::logical($route?->getName());

        if ($name === null) {
            return $next($request);
        }

        $parameters = $route->parameters();

        unset($parameters['locale']);

        return redirect()->to(
            $this->urls->route($name, [...$request->query(), ...$parameters], $this->contexts->default()->default()),
            301,
        );
    }
}
