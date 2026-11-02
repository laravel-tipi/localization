<?php

declare(strict_types=1);

namespace Tipi\Localization\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tipi\Localization\LocaleContexts;
use Tipi\Localization\Localization;
use Tipi\Localization\Support\SafeRedirect;

/**
 * GET {switch uri}?redirect=/path&context=name
 *
 * Records an explicit locale choice for the context and redirects to a
 * same-site path. Anything else (absolute URLs, "//host", backslashes)
 * redirects to "/" instead, so the endpoint cannot be used as an open
 * redirect.
 *
 * @internal
 */
final readonly class SwitchLocaleController
{
    public function __construct(
        private Localization $localization,
        private LocaleContexts $contexts,
    ) {}

    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $name = $request->query('context');

        if ($name !== null && (! is_string($name) || ! $this->contexts->has($name))) {
            abort(404);
        }

        $context = $this->contexts->get($name ?? $this->contexts->default()->name);

        if (! $context->supports($locale)) {
            abort(404);
        }

        $this->localization->select($locale, $context->name);

        return redirect()
            ->to(SafeRedirect::path($request->query('redirect')))
            ->setVary(['Cookie'], false);
    }
}
