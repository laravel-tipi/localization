<?php

declare(strict_types=1);

namespace Tipi\Localization\Repositories;

use Illuminate\Support\Collection;
use Tipi\Localization\Contracts\LocaleRepository;
use Tipi\Localization\Models\LocaleModel;

final class DatabaseLocaleRepository implements LocaleRepository
{
    public function all(): Collection
    {
        return LocaleModel::query()
            ->get()
            ->mapWithKeys(
                fn (LocaleModel $locale): array => [
                    $locale->getKey() => $locale->toLocale(),
                ],
            );
    }
}
