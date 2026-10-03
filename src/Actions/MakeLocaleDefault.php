<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Illuminate\Support\Facades\DB;
use Throwable;
use Tipi\Localization\Exceptions\LocaleCannotBeMadeDefault;
use Tipi\Localization\Models\Locale;

final readonly class MakeLocaleDefault
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

            Locale::query()
                ->whereKeyNot($locale->getKey())
                ->update(['is_default' => false]);

            if (! $locale->canBeMadeDefault()) {
                throw new LocaleCannotBeMadeDefault($locale->getKey());
            }

            $locale->is_default = true;
            $locale->save();
        });
    }
}
