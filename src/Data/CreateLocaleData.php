<?php

declare(strict_types=1);

namespace Tipi\Localization\Data;

use Tipi\Localization\Exceptions\InvalidLocaleDataException;
use Tipi\Support\Data\DataTransferObject;
use Tipi\Support\Enums\TextDirection;

final readonly class CreateLocaleData implements DataTransferObject
{
    /**
     * @param  bool  $active  new locales are inactive unless requested; the first locale is always active
     *
     * @throws InvalidLocaleDataException
     */
    public function __construct(
        public string $code,
        public string $name,
        public string $nativeName,
        public ?string $nativeAbbreviation = null,
        public ?string $countryCode = null,
        public TextDirection $textDirection = TextDirection::Ltr,
        public bool $active = false,
    ) {
        LocaleDataValidation::code($code);
        LocaleDataValidation::metadata($name, $nativeName, $nativeAbbreviation, $countryCode);
    }

    public static function fromArray(array $data): static
    {
        return new self(
            code: LocaleDataValidation::requiredString($data, 'code'),
            name: LocaleDataValidation::requiredString($data, 'name'),
            nativeName: LocaleDataValidation::requiredString($data, 'native_name'),
            nativeAbbreviation: LocaleDataValidation::optionalString($data, 'native_abbreviation'),
            countryCode: LocaleDataValidation::optionalString($data, 'country_code'),
            textDirection: LocaleDataValidation::direction($data['text_direction'] ?? TextDirection::Ltr),
            active: (bool) ($data['active'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'native_name' => $this->nativeName,
            'native_abbreviation' => $this->nativeAbbreviation,
            'country_code' => $this->countryCode,
            'text_direction' => $this->textDirection->value,
            'active' => $this->active,
        ];
    }
}
