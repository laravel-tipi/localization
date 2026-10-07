<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Illuminate\Support\Facades\DB;
use Throwable;
use Tipi\Localization\Exceptions\LocaleCannotBeActivatedException;
use Tipi\Localization\LocaleRegistry;
use Tipi\Localization\Models\Locale;

final readonly class ActivateLocale
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

            if (! $locale->canBeActivated()) {
                throw new LocaleCannotBeActivatedException($locale->getKey());
            }

            $locale->is_active = true;
            $locale->save();
        });

        $this->locales->flush();
    }
}
