<?php

declare(strict_types=1);

namespace Tipi\Localization\Data;

use Tipi\Localization\Exceptions\InvalidLocaleDataException;
use Tipi\Support\Data\DataTransferObject;

final readonly class UpdateLocaleContextData implements DataTransferObject
{
    /**
     * @param  list<string>|null  $locales  null includes every active locale
     * @param  ?string  $default  null follows the global default locale
     *
     * @throws InvalidLocaleDataException
     */
    public function __construct(
        public ?array $locales = null,
        public ?string $default = null,
    ) {
        LocaleDataValidation::contextLocales($locales, $default);
    }

    public static function fromArray(array $data): static
    {
        return new self(
            locales: LocaleDataValidation::localeList($data),
            default: LocaleDataValidation::optionalString($data, 'default'),
        );
    }

    public function toArray(): array
    {
        return [
            'locales' => $this->locales ?? '*',
            'default' => $this->default,
        ];
    }
}
