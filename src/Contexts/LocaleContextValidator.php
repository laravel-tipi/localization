<?php

declare(strict_types=1);

namespace Tipi\Localization\Contexts;

use Tipi\Localization\Exceptions\InvalidLocaleContextException;
use Tipi\Localization\LocaleRegistry;
use Tipi\Localization\Support\ContextName;

/**
 * Enforces the context invariants:
 *
 * 1. a locale list contains the global default locale;
 * 2. a context default belongs to the context;
 * 3. every listed locale exists in the global registry;
 * 4. (before storing) the context default is active.
 *
 * @internal
 */
final readonly class LocaleContextValidator
{
    public function __construct(
        private LocaleRegistry $locales,
    ) {}

    /**
     * @throws InvalidLocaleContextException
     */
    public function validate(LocaleContextDefinition $definition, bool $forStorage = false): void
    {
        $name = $definition->name;

        if (! ContextName::isValid($name)) {
            throw new InvalidLocaleContextException(
                $name,
                'the name must start with a lowercase letter and contain only lowercase letters, digits, "-" and "_" (max '.ContextName::MAX_LENGTH.' characters).',
            );
        }

        if ($definition->locales !== null) {
            if ($definition->locales === []) {
                throw new InvalidLocaleContextException($name, 'it must contain at least one locale (or "*" for all).');
            }

            foreach ($definition->locales as $code) {
                if ($this->locales->find($code) === null) {
                    throw new InvalidLocaleContextException($name, "locale [{$code}] does not exist in the locale registry.");
                }
            }

            $global = $this->locales->defaultCode();

            if (! in_array($global, $definition->locales, true)) {
                throw new InvalidLocaleContextException(
                    $name,
                    "it must contain the global default locale [{$global}].",
                );
            }
        }

        if ($definition->default === null) {
            return;
        }

        $default = $this->locales->find($definition->default);

        if ($default === null || ! $definition->includes($definition->default)) {
            throw new InvalidLocaleContextException(
                $name,
                "its default locale [{$definition->default}] is not one of its locales.",
            );
        }

        if ($forStorage && ! $default->isActive()) {
            throw new InvalidLocaleContextException(
                $name,
                "its default locale [{$definition->default}] is not active.",
            );
        }
    }
}
