<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Illuminate\Support\Facades\DB;
use Throwable;
use Tipi\Localization\Data\UpdateLocaleData;
use Tipi\Localization\LocaleModelResolver;
use Tipi\Localization\LocaleRegistry;
use Tipi\Localization\Models\Locale;

final readonly class UpdateLocale
{
    public function __construct(
        private LocaleRegistry $locales,
        private LocaleModelResolver $modelResolver,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(string $code, UpdateLocaleData $data): Locale
    {
        $locale = DB::transaction(function () use ($code, $data): Locale {
            $model = $this->modelResolver->class();

            $locale = $model::query()
                ->lockForUpdate()
                ->findOrFail($code);

            $locale->name = $data->name;
            $locale->native_name = $data->nativeName;
            $locale->country_code = $data->countryCode;
            $locale->text_direction = $data->textDirection;

            $locale->save();

            return $locale;
        });

        $this->locales->flush();

        return $locale;
    }
}
