<?php

declare(strict_types=1);

namespace Tipi\Localization\Repositories;

use Illuminate\Support\Collection;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Contracts\LocaleRepository;
use Tipi\Localization\Models\Locale;

final readonly class DatabaseLocaleRepository implements LocaleRepository
{
    public function __construct(
        private LocalizationConfig $config,
    ) {}

    public function all(): Collection
    {
        $model = $this->config->localeModel;

        return $model::query()
            ->ordered()
            ->get()
            ->mapWithKeys(fn (Locale $locale): array => [
                $locale->code => $locale->toLocale(),
            ])
            ->toBase();
    }
}
