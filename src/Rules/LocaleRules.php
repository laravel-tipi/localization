<?php

declare(strict_types=1);

namespace Tipi\Localization\Rules;

use Illuminate\Validation\Rule;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Support\LocaleCode as LocaleCodeFormat;
use Tipi\Support\Enums\TextDirection;

/**
 * Form validation rules matching CreateLocaleData / UpdateLocaleData.
 * The DTOs validate again, so these exist for friendly field errors.
 */
final class LocaleRules
{
    /**
     * @return array<string, list<mixed>>
     */
    public static function create(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:'.LocaleCodeFormat::MAX_LENGTH,
                new LocaleCode,
                Rule::unique(app(LocalizationConfig::class)->localeModel, 'code'),
            ],
            ...self::update(),
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function update(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'native_name' => ['required', 'string', 'max:255'],
            'native_abbreviation' => ['nullable', 'string', 'max:3'],
            'country_code' => ['nullable', 'string', new CountryCode],
            'text_direction' => ['nullable', Rule::enum(TextDirection::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'code' => 'locale code',
            'name' => 'name',
            'native_name' => 'native name',
            'native_abbreviation' => 'native abbreviation',
            'country_code' => 'country code',
            'text_direction' => 'text direction',
        ];
    }
}
