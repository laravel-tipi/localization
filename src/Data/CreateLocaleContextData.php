<?php

declare(strict_types=1);

namespace Tipi\Localization\Data;

use Tipi\Localization\Exceptions\InvalidLocaleDataException;
use Tipi\Support\Data\DataTransferObject;

final readonly class CreateLocaleContextData implements DataTransferObject
{
    /**
     * @param  list<string>|null  $locales  null includes every active locale
     * @param  ?string  $default  null follows the global default locale
     *
     * @throws InvalidLocaleDataException
     */
    public function __construct(
        public string $name,
        public ?array $locales = null,
        public ?string $default = null,
    ) {
        LocaleDataValidation::contextName($name);
        LocaleDataValidation::contextLocales($locales, $default);
    }

    public static function fromArray(array $data): static
    {
        return new self(
            name: LocaleDataValidation::requiredString($data, 'name'),
            locales: LocaleDataValidation::localeList($data),
            default: LocaleDataValidation::optionalString($data, 'default'),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'locales' => $this->locales ?? '*',
            'default' => $this->default,
        ];
    }
}
