<?php

declare(strict_types=1);

namespace Tipi\Localization\Data;

use Tipi\Localization\Enums\TextDirection;
use Tipi\Localization\Support\Data\DataTransferObject;

final readonly class UpdateLocaleData implements DataTransferObject
{
    public function __construct(
        public string $name,
        public string $nativeName,
        public ?string $countryCode,
        public TextDirection $textDirection,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            nativeName: $data['native_name'],
            countryCode: $data['country_code'] ?? null,
            textDirection: TextDirection::from(
                $data['text_direction'] ?? TextDirection::Ltr->value,
            ),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'native_name' => $this->nativeName,
            'country_code' => $this->countryCode,
            'text_direction' => $this->textDirection->value,
        ];
    }
}

