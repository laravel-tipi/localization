<?php

declare(strict_types=1);

namespace Tipi\Localization\Tests\Fixtures;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tipi\Localization\Facades\Localization;

/**
 * Route middleware used by tests: runs after the localized middleware.
 */
final class EnsureLocalizedParameters
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->route('locale') === null
            && $request->route('wine') instanceof Wine
            && Localization::currentCode() === app()->getLocale(),
            418,
        );

        return $next($request);
    }
}
