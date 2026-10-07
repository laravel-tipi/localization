<?php

declare(strict_types=1);

namespace Tipi\Localization;

use Tipi\Support\Enums\TextDirection;

final readonly class Locale
{
    public function __construct(
        public string $code,
        public string $name,
        public string $nativeName,
        public ?string $countryCode,
        public TextDirection $textDirection,
        private bool $active,
        private bool $default,
    ) {}

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isInactive(): bool
    {
        return ! $this->active;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }
}
