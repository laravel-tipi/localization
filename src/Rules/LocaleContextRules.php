<?php

declare(strict_types=1);

namespace Tipi\Localization\Rules;

use Illuminate\Validation\Rule;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Support\ContextName;

/**
 * Form validation rules matching the context DTOs. Context invariants
 * (global default included, default is a member, ...) depend on the
 * registry and are enforced by the actions, which throw
 * InvalidLocaleContextException with an explanation.
 */
final class LocaleContextRules
{
    /**
     * @return array<string, list<mixed>>
     */
    public static function create(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:'.ContextName::MAX_LENGTH,
                'regex:/^'.ContextName::PATTERN.'$/',
                Rule::unique(app(LocalizationConfig::class)->contextModel, 'name'),
                Rule::notIn(array_keys(app(LocalizationConfig::class)->contexts)),
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
            'locales' => ['nullable', 'array', 'min:1'],
            'locales.*' => ['string', 'distinct', new LocaleCode],
            'default' => ['nullable', 'string', new LocaleCode],
        ];
    }
}
