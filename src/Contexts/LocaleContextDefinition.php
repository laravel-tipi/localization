<?php

declare(strict_types=1);

namespace Tipi\Localization\Contexts;

/**
 * A context as it is stored, before it is resolved against the registry.
 */
final readonly class LocaleContextDefinition
{
    /**
     * @param  list<string>|null  $locales  null means every active locale
     * @param  ?string  $default  null means "follow the global default"
     * @param  bool  $required  declared in config; cannot be deleted
     * @param  bool  $persisted  stored in the database
     */
    public function __construct(
        public string $name,
        public ?array $locales,
        public ?string $default,
        public bool $required,
        public bool $persisted,
    ) {}

    public function includesAllLocales(): bool
    {
        return $this->locales === null;
    }

    public function includes(string $code): bool
    {
        return $this->locales === null || in_array($code, $this->locales, true);
    }
}
