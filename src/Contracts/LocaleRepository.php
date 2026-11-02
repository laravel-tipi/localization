<?php

declare(strict_types=1);

namespace Tipi\Localization\Contracts;

use Illuminate\Support\Collection;
use Tipi\Support\Locale;

interface LocaleRepository
{
    /**
     * Every locale, active or not, keyed by code in display order.
     *
     * @return Collection<string, Locale>
     */
    public function all(): Collection;
}
