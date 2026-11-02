<?php

declare(strict_types=1);

namespace Tipi\Localization\Data;

use Tipi\Localization\Exceptions\InvalidLocaleDataException;
use Tipi\Support\Data\DataTransferObject;
use Tipi\Support\Enums\TextDirection;

/**
 * Locale metadata. The code, active state and default state are changed
 * by their own actions.
 */
final readonly class UpdateLocaleData implements DataTransferObject
{
    /**
     * @throws InvalidLocaleDataException
     */
    public function __construct(
        public string $name,
        public string $nativeName,
        public ?string $nativeAbbreviation = null,
        public ?string $countryCode = null,
        public TextDirection $textDirection = TextDirection::Ltr,
    ) {
        LocaleDataValidation::metadata($name, $nativeName, $nativeAbbreviation, $countryCode);
    }

    public static function fromArray(array $data): static
    {
        return new self(
            name: LocaleDataValidation::requiredString($data, 'name'),
            nativeName: LocaleDataValidation::requiredString($data, 'native_name'),
            nativeAbbreviation: LocaleDataValidation::optionalString($data, 'native_abbreviation'),
            countryCode: LocaleDataValidation::optionalString($data, 'country_code'),
            textDirection: LocaleDataValidation::direction($data['text_direction'] ?? TextDirection::Ltr),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'native_name' => $this->nativeName,
            'native_abbreviation' => $this->nativeAbbreviation,
            'country_code' => $this->countryCode,
            'text_direction' => $this->textDirection->value,
        ];
    }
}
