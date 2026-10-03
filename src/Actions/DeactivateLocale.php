<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Illuminate\Support\Facades\DB;
use Throwable;
use Tipi\Localization\Exceptions\LocaleCannotBeDeactivated;
use Tipi\Localization\Models\Locale;

final readonly class DeactivateLocale
{
    /**
     * @throws Throwable
     */
    public function execute(string $localeCode): void
    {
        DB::transaction(function () use ($localeCode) {
            $locale = Locale::query()
                ->lockForUpdate()
                ->findOrFail($localeCode);

            if (! $locale->canBeDeactivated()) {
                throw new LocaleCannotBeDeactivated($locale->getKey());
            }

            $locale->is_active = false;
            $locale->save();
        });
    }
}
