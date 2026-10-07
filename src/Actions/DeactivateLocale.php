<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Illuminate\Support\Facades\DB;
use Throwable;
use Tipi\Localization\Exceptions\LocaleCannotBeDeactivatedException;
use Tipi\Localization\LocaleModelResolver;
use Tipi\Localization\LocaleRegistry;

final readonly class DeactivateLocale
{
    public function __construct(
        private LocaleRegistry $locales,
        private LocaleModelResolver $modelResolver,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(string $code): void
    {
        DB::transaction(function () use ($code) {
            $model = $this->modelResolver->class();

            $locale = $model::query()
                ->lockForUpdate()
                ->findOrFail($code);

            if (! $locale->canBeDeactivated()) {
                throw new LocaleCannotBeDeactivatedException($locale->getKey());
            }

            $locale->is_active = false;
            $locale->save();
        });

        $this->locales->flush();
    }
}
