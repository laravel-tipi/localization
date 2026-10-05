<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Tipi\Localization\LocaleRegistry;

final readonly class UpdateLocale
{
    public function __construct(
        private LocaleRegistry $locales,
    ) {}

}
