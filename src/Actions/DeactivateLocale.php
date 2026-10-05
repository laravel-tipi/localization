<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Illuminate\Support\Facades\DB;
use Throwable;
use Tipi\Localization\Exceptions\LocaleCannotBeDeactivatedException;
use Tipi\Localization\LocaleRegistry;
use Tipi\Localization\Models\LocaleModel;

final readonly class DeactivateLocale
{
    public function __construct(
        private LocaleRegistry $locales,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(string $localeCode): void
    {
        DB::transaction(function () use ($localeCode) {
            $locale = LocaleModel::query()
                ->lockForUpdate()
                ->findOrFail($localeCode);

            if (! $locale->canBeDeactivated()) {
                throw new LocaleCannotBeDeactivatedException($locale->getKey());
            }

            $locale->is_active = false;
            $locale->save();
        });

        $this->locales->flush();
    }
}
