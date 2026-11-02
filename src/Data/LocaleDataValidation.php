<?php

declare(strict_types=1);

namespace Tipi\Localization\Data;

use Tipi\Localization\Exceptions\InvalidLocaleDataException;
use Tipi\Localization\Support\ContextName;
use Tipi\Localization\Support\CountryCode;
use Tipi\Localization\Support\LocaleCode;
use Tipi\Support\Enums\TextDirection;

/**
 * @internal
 */
final class LocaleDataValidation
{
    public static function code(string $code, string $field = 'code'): void
    {
        if (! LocaleCode::isValid($code)) {
            throw new InvalidLocaleDataException(
                "[{$field}] must be a valid locale code such as en, en-US or zh-Hans, got [{$code}].",
            );
        }
    }

    public static function contextName(string $name): void
    {
        if (! ContextName::isValid($name)) {
            throw new InvalidLocaleDataException(
                '[name] must start with a lowercase letter and contain only lowercase letters, digits, "-" and "_" (max '.ContextName::MAX_LENGTH." characters), got [{$name}].",
            );
        }
    }

    public static function metadata(
        string $name,
        string $nativeName,
        ?string $nativeAbbreviation,
        ?string $countryCode,
    ): void {
        foreach (['name' => $name, 'native_name' => $nativeName] as $field => $value) {
            if (trim($value) === '' || mb_strlen($value) > 255) {
                throw new InvalidLocaleDataException("[{$field}] must be between 1 and 255 characters.");
            }
        }

        if ($nativeAbbreviation !== null && (trim($nativeAbbreviation) === '' || mb_strlen($nativeAbbreviation) > 3)) {
            throw new InvalidLocaleDataException('[native_abbreviation] must be between 1 and 3 characters.');
        }

        if ($countryCode !== null && ! CountryCode::isValid($countryCode)) {
            throw new InvalidLocaleDataException(
                "[country_code] must be two uppercase letters, got [{$countryCode}].",
            );
        }
    }

    /**
     * @param  list<string>|null  $locales
     */
    public static function contextLocales(?array $locales, ?string $default): void
    {
        if ($locales !== null) {
            if ($locales === [] || ! array_is_list($locales)) {
                throw new InvalidLocaleDataException('[locales] must be null (all locales) or a non-empty list of codes.');
            }

            foreach ($locales as $code) {
                self::code($code, 'locales');
            }

            if (count(array_unique($locales)) !== count($locales)) {
                throw new InvalidLocaleDataException('[locales] must not contain duplicates.');
            }
        }

        if ($default !== null) {
            self::code($default, 'default');
        }
    }

    public static function direction(mixed $value): TextDirection
    {
        if ($value instanceof TextDirection) {
            return $value;
        }

        $direction = is_string($value) ? TextDirection::tryFrom($value) : null;

        return $direction ?? throw new InvalidLocaleDataException('[text_direction] must be "ltr" or "rtl".');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function optionalString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if ($value !== null && ! is_string($value)) {
            throw new InvalidLocaleDataException("[{$key}] must be a string or null.");
        }

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function requiredString(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value)) {
            throw new InvalidLocaleDataException("[{$key}] is required and must be a string.");
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>|null
     */
    public static function localeList(array $data): ?array
    {
        $value = $data['locales'] ?? null;

        if ($value === null || $value === '*') {
            return null;
        }

        if (! is_array($value) || ! array_is_list($value)) {
            throw new InvalidLocaleDataException('[locales] must be "*", null or a list of codes.');
        }

        foreach ($value as $code) {
            if (! is_string($code)) {
                throw new InvalidLocaleDataException('[locales] must only contain strings.');
            }
        }

        return $value;
    }
}
