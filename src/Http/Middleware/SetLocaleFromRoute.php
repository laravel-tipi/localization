<?php

declare(strict_types=1);

namespace Tipi\Localization\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tipi\Localization\Exceptions\InvalidLocalizedRouteException;
use Tipi\Localization\LocaleContexts;
use Tipi\Localization\Routing\LocalizedRoutes;

/**
 * Applied to localized routes: selects the default context and sets the
 * application locale from the {locale} segment (or the context default
 * for unprefixed routes). Unsupported codes are a 404.
 *
 * The {locale} parameter is then removed from the route: Laravel passes
 * scalar route parameters to closures and controllers by position, so
 * keeping it would shift every argument (show(string $slug) would get
 * "ka"). Use Localization::current() instead of $request->route('locale').
 *
 * @internal
 */
final readonly class SetLocaleFromRoute
{
    public function __construct(
        private LocaleContexts $contexts,
        private Application $app,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $context = $this->contexts->setCurrent($this->contexts->default()->name);

        $code = $request->route('locale');

        $locale = match (true) {
            $code === null => $context->default(),
            is_string($code) => $context->find($code),
            default => null,
        };

        if ($locale === null) {
            abort(404);
        }

        $reserved = (array) $request->route()?->getMetadata(LocalizedRoutes::RESERVED_METADATA, []);

        // A supported locale whose code equals a static route segment makes
        // either the page or the locale unreachable: fail loudly instead of
        // serving the wrong content. The actions reject this up front.
        foreach ($context->codes() as $code) {
            if (in_array($code, $reserved, true)) {
                throw InvalidLocalizedRouteException::reservedLocale($code);
            }
        }

        $request->route()?->forgetParameter('locale');

        $this->app->setLocale($locale->code);

        return $next($request);
    }
}
