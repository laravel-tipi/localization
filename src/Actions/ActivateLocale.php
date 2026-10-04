<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Illuminate\Support\Facades\DB;
use Throwable;
use Tipi\Localization\Exceptions\LocaleCannotBeActivated;
use Tipi\Localization\Models\LocaleModel;

final readonly class ActivateLocale
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

            if (! $locale->canBeActivated()) {
                throw new LocaleCannotBeActivated($locale->getKey());
            }

            $locale->is_active = true;
            $locale->save();
        });
    }
}
