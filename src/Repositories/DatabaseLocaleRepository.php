<?php

declare(strict_types=1);

namespace Tipi\Localization\Repositories;

use Illuminate\Support\Collection;
use Tipi\Localization\Contracts\LocaleRepository;
use Tipi\Localization\Models\Locale;
use Tipi\Support\Locale as LocaleData;

final class DatabaseLocaleRepository implements LocaleRepository
{
    /**
     * @return Collection<string, LocaleData>
     */
    public function all(): Collection
    {
        return Locale::query()
            ->get()
            ->mapWithKeys(
                fn (Locale $locale): array => [
                    $locale->code => $locale->toLocale(),
                ],
            );
    }
}
