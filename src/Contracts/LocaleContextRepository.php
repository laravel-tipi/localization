<?php

declare(strict_types=1);

namespace Tipi\Localization\Contracts;

use Illuminate\Support\Collection;
use Tipi\Localization\Contexts\LocaleContextDefinition;

interface LocaleContextRepository
{
    /**
     * Every context definition keyed by name. Always contains the
     * default context.
     *
     * @return Collection<string, LocaleContextDefinition>
     */
    public function all(): Collection;
}
