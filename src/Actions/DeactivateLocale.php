<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Illuminate\Support\Facades\DB;
use Throwable;
use Tipi\Localization\Exceptions\LocaleCannotBeDeactivatedException;
use Tipi\Localization\Models\LocaleModel;

final readonly class DeactivateLocale
{
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
    }
}
