<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Illuminate\Support\Facades\DB;
use Throwable;
use Tipi\Localization\Data\CreateLocaleData;
use Tipi\Localization\LocaleRegistry;
use Tipi\Localization\Models\LocaleModel;

final readonly class CreateLocale
{
    public function __construct(
        private LocaleRegistry $locales,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(CreateLocaleData $data): LocaleModel
    {
        $locale = DB::transaction(function () use ($data): LocaleModel {
            $defaultLocaleExists = LocaleModel::query()
                ->where('is_default', true)
                ->lockForUpdate()
                ->exists();

            $locale = new LocaleModel;

            $locale->code = $data->code;
            $locale->name = $data->name;
            $locale->native_name = $data->nativeName;
            $locale->country_code = $data->countryCode;
            $locale->text_direction = $data->textDirection;
            $locale->is_active = true;
            $locale->is_default = ! $defaultLocaleExists;

            $locale->save();

            return $locale;
        });

        $this->locales->flush();

        return $locale;
    }
}
