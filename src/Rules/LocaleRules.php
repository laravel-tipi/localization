<?php

declare(strict_types=1);

namespace Tipi\Localization\Rules;

use Illuminate\Validation\Rule;
use Tipi\Support\Enums\TextDirection;

final class LocaleRules
{
    public static function create(): array
    {
        return [
            'code' => [
                'required',
                'string',
                new LocaleCode,
                Rule::unique('locales', 'code'),
            ],
            ...self::rules(),
        ];
    }

    public static function update(): array
    {
        return self::rules();
    }

    public static function attributes(): array
    {
        return [
            'code' => 'locale code',
            'name' => 'name',
            'native_name' => 'native name',
            'country_code' => 'country code',
            'text_direction' => 'text direction',
        ];
    }

    private static function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'native_name' => [
                'required',
                'string',
                'max:255',
            ],
            'country_code' => [
                'nullable',
                new CountryCode,
            ],
            'text_direction' => [
                'required',
                Rule::enum(TextDirection::class),
            ],
        ];
    }
}
