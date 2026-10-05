<?php

declare(strict_types=1);

namespace Tipi\Localization\Support\Data;

interface DataTransferObject
{
    public static function fromArray(array $data): static;

    public function toArray(): array;
}
