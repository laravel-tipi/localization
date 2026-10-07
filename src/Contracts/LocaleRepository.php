<?php

declare(strict_types=1);

namespace Tipi\Localization\Contracts;

use Illuminate\Support\Collection;
use Tipi\Support\Locale;

interface LocaleRepository
{
    /**
     * @return Collection<string, Locale>
     */
    public function all(): Collection;
}
