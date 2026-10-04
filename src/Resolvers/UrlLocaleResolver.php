<?php

declare(strict_types=1);

namespace Tipi\Localization\Resolvers;

use Illuminate\Http\Request;
use Tipi\Localization\Locale;
use Tipi\Localization\LocaleRegistry;

final readonly class UrlLocaleResolver
{
    public function __construct(
        private LocaleRegistry $locales,
    ) {}

    public function resolve(Request $request): ?Locale
    {
        $code = $request->segment(1);

        if ($code === null) {
            return null;
        }

        if (! in_array($code, $this->locales->supportedCodes(), true)) {
            return null;
        }

        return $this->locales->supportedLocale($code);
    }
}
