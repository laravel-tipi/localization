<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Data\CreateLocaleData;
use Tipi\Localization\Database\LocalizationDatabase;
use Tipi\Localization\Exceptions\InvalidLocaleOperationException;
use Tipi\Localization\Exceptions\InvalidLocalizedRouteException;
use Tipi\Localization\Models\Locale;
use Tipi\Localization\Routing\LocalizedRoutes;

/**
 * Creates a locale at the end of the display order. The first locale
 * becomes the active global default; later locales are inactive unless
 * the data says otherwise.
 */
final readonly class CreateLocale
{
    public function __construct(
        private LocalizationDatabase $database,
        private LocalizationConfig $config,
        private LocalizedRoutes $routes,
    ) {}

    /**
     * @throws InvalidLocaleOperationException
     * @throws InvalidLocalizedRouteException when the code equals a static localized route segment
     */
    public function execute(CreateLocaleData $data): Locale
    {
        $this->database->ensureLocalesDriver();
        $this->routes->ensureNotReserved($data->code);

        return $this->database->transaction(function () use ($data): Locale {
            $model = $this->config->localeModel;

            if ($model::query()->whereKey($data->code)->exists()) {
                throw new InvalidLocaleOperationException("Locale [{$data->code}] already exists.");
            }

            $first = ! $model::query()->where('is_default', true)->lockForUpdate()->exists();

            $locale = new $model;
            $locale->code = $data->code;
            $locale->name = $data->name;
            $locale->native_name = $data->nativeName;
            $locale->native_abbreviation = $data->nativeAbbreviation;
            $locale->country_code = $data->countryCode;
            $locale->text_direction = $data->textDirection;
            $locale->position = ((int) $model::query()->max('position')) + ($model::query()->exists() ? 1 : 0);
            $locale->is_active = $first || $data->active;
            $locale->is_default = $first;
            $locale->save();

            return $locale;
        });
    }
}
