<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Illuminate\Support\Facades\DB;
use Throwable;
use Tipi\Localization\Exceptions\LocaleCannotBeMadeDefaultException;
use Tipi\Localization\LocaleRegistry;
use Tipi\Localization\Models\Locale;

final readonly class MakeLocaleDefault
{
    public function __construct(
        private LocaleRegistry $locales,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(string $code): void
    {
        DB::transaction(function () use ($code) {
            $locale = Locale::query()
                ->lockForUpdate()
                ->findOrFail($code);

            if (! $locale->canBeMadeDefault()) {
                throw new LocaleCannotBeMadeDefaultException($locale->getKey());
            }

            Locale::query()
                ->whereKeyNot($locale->getKey())
                ->update(['is_default' => false]);

            $locale->is_default = true;
            $locale->save();
        });

        $this->locales->flush();
    }
}
