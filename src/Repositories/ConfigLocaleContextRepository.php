<?php

declare(strict_types=1);

namespace Tipi\Localization\Repositories;

use Illuminate\Support\Collection;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Contexts\LocaleContextDefinition;
use Tipi\Localization\Contracts\LocaleContextRepository;

final readonly class ConfigLocaleContextRepository implements LocaleContextRepository
{
    public function __construct(
        private LocalizationConfig $config,
    ) {}

    public function all(): Collection
    {
        return (new Collection($this->config->contexts))->map(
            fn (array $context, string $name): LocaleContextDefinition => new LocaleContextDefinition(
                name: $name,
                locales: $context['locales'],
                default: $context['default'],
                required: true,
                persisted: false,
            ),
        );
    }
}
