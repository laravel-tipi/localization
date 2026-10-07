<?php

declare(strict_types=1);

namespace Tipi\Localization\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Tipi\Localization\Support\CountryCode as CountryCodeSupport;

final class CountryCode implements ValidationRule
{
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail,
    ): void {
        if (
            ! is_string($value) ||
            ! CountryCodeSupport::isValid($value)
        ) {
            $fail('The :attribute must be a valid country code.');
        }
    }
}