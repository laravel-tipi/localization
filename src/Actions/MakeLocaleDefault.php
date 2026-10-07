<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Illuminate\Support\Facades\DB;
use Throwable;
use Tipi\Localization\Exceptions\LocaleCannotBeMadeDefaultException;
use Tipi\Localization\LocaleModelResolver;
use Tipi\Localization\LocaleRegistry;

final readonly class MakeLocaleDefault
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

            if (! $locale->canBeMadeDefault()) {
                throw new LocaleCannotBeMadeDefaultException($locale->getKey());
            }

            $model::query()
                ->whereKeyNot($locale->getKey())
                ->update(['is_default' => false]);

            $locale->is_default = true;
            $locale->save();
        });

        $this->locales->flush();
    }
}
