<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Tipi\Localization\Data\UpdateLocaleData;
use Tipi\Localization\Database\LocalizationDatabase;
use Tipi\Localization\Exceptions\LocaleNotFoundException;
use Tipi\Localization\Models\Locale;

final readonly class UpdateLocale
{
    public function __construct(
        private LocalizationDatabase $database,
    ) {}

    /**
     * @throws LocaleNotFoundException
     */
    public function execute(string $code, UpdateLocaleData $data): Locale
    {
        $this->database->ensureLocalesDriver();

        return $this->database->transaction(function () use ($code, $data): Locale {
            $locale = $this->database->lockLocale($code);

            $locale->name = $data->name;
            $locale->native_name = $data->nativeName;
            $locale->native_abbreviation = $data->nativeAbbreviation;
            $locale->country_code = $data->countryCode;
            $locale->text_direction = $data->textDirection;
            $locale->save();

            return $locale;
        });
    }
}
