<?php

declare(strict_types=1);

namespace Tipi\Localization\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\LocaleContexts;
use Tipi\Localization\LocaleNegotiator;
use Tipi\Localization\Routing\LocalizedUrlGenerator;

/**
 * "/" when every locale is prefixed (hide_default_locale = false):
 * redirects to the negotiated locale's root route, or to the default
 * locale's root route when negotiation is disabled.
 *
 * @internal
 */
final readonly class RootLocaleRedirectController
{
    public function __construct(
        private LocaleContexts $contexts,
        private LocaleNegotiator $negotiator,
        private LocalizedUrlGenerator $urls,
        private LocalizationConfig $config,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $context = $this->contexts->setCurrent($this->contexts->default()->name);

        $locale = $this->config->negotiateRootLocale
            ? $this->negotiator->negotiate($request, $context)
            : $context->default();

        return redirect()
            ->to($this->urls->route($this->config->rootRoute, locale: $locale))
            ->setVary(['Accept-Language', 'Cookie'], false);
    }
}
