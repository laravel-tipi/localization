<?php

declare(strict_types=1);

namespace Tipi\Localization;

final class LocaleResolver
{
    private ?Locale $locale = null;

    public function __construct(
        private readonly LocaleRegistry $locales,
    ) {}

    public function current(): Locale
    {
        return $this->locale ??= $this->resolve();
    }

    public function currentCode(): string
    {
        return $this->current()->code;
    }

    private function resolve(): Locale
    {
        return $this->locales->supportedLocale(
            app()->getLocale(),
        );
    }
}
