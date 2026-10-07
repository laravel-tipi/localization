<?php

declare(strict_types=1);

namespace Tipi\Localization\Data;

use Tipi\Support\Data\DataTransferObject;
use Tipi\Support\Enums\TextDirection;

final readonly class CreateLocaleData implements DataTransferObject
{
    public function __construct(
        public string $code,
        public string $name,
        public string $nativeName,
        public ?string $countryCode,
        public TextDirection $textDirection,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
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
            'code' => $this->code,
            'name' => $this->name,
            'native_name' => $this->nativeName,
            'country_code' => $this->countryCode,
            'text_direction' => $this->textDirection->value,
        ];
    }
}
