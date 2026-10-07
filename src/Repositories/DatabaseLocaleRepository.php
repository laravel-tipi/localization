<?php

declare(strict_types=1);

namespace Tipi\Localization\Repositories;

use Illuminate\Support\Collection;
use Tipi\Localization\Contracts\LocaleRepository;
use Tipi\Localization\LocaleModelResolver;
use Tipi\Localization\Models\Locale;
use Tipi\Support\Locale as LocaleData;

final readonly class DatabaseLocaleRepository implements LocaleRepository
{
    public function __construct(
        private LocaleModelResolver $modelResolver,
    ) {}

    /**
     * @return Collection<string, LocaleData>
     */
    public function all(): Collection
    {
        $model = $this->modelResolver->class();

        return $model::query()
            ->get()
            ->mapWithKeys(
                fn (Locale $locale): array => [
                    $locale->getLocaleCode() => $locale->toLocale(),
                ],
            );
    }
}
