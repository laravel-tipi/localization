<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Tipi\Localization\LocaleRegistry;
use Tipi\Localization\LocaleResolver;
use Tipi\Support\Locale;

final readonly class SetCurrentLocale
{
    public function __construct(
        private LocaleRegistry $locales,
        private LocaleResolver $resolver,
    ) {}

    public function execute(string|Locale $locale): Locale
    {
        if (is_string($locale)) {
            $locale = $this->locales->supportedLocale($locale);
        }

        app()->setLocale($locale->code);

        $this->resolver->set($locale);

        return $locale;
    }
}
