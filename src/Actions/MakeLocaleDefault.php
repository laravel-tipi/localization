<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Illuminate\Support\Facades\DB;
use Throwable;
use Tipi\Localization\Exceptions\LocaleCannotBeMadeDefaultException;
use Tipi\Localization\Models\LocaleModel;

final readonly class MakeLocaleDefault
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

            if (! $locale->canBeMadeDefault()) {
                throw new LocaleCannotBeMadeDefaultException($locale->getKey());
            }

            LocaleModel::query()
                ->whereKeyNot($locale->getKey())
                ->update(['is_default' => false]);

            $locale->is_default = true;
            $locale->save();
        });
    }
}
