<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Illuminate\Support\Facades\DB;
use Throwable;
use Tipi\Localization\Data\UpdateLocaleData;
use Tipi\Localization\LocaleRegistry;
use Tipi\Localization\Models\LocaleModel;

final readonly class UpdateLocale
{
    public function __construct(
        private LocaleRegistry $locales,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(string $code, UpdateLocaleData $data): LocaleModel
    {
        $locale = DB::transaction(function () use ($code, $data): LocaleModel {
            $locale = LocaleModel::query()
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
