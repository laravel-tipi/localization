<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Tipi\Localization\Contexts\LocaleContextDefinition;
use Tipi\Localization\Database\LocalizationDatabase;
use Tipi\Localization\Exceptions\InvalidLocaleOperationException;
use Tipi\Localization\Exceptions\LocaleNotFoundException;
use Tipi\Localization\LocaleContexts;

/**
 * Deletes an inactive, non-default locale and its stored context
 * memberships. Rejected while a config-defined context lists it.
 */
final readonly class DeleteLocale
{
    public function __construct(
        private LocalizationDatabase $database,
        private LocaleContexts $contexts,
    ) {}

    /**
     * @throws LocaleNotFoundException
     * @throws InvalidLocaleOperationException
     */
    public function execute(string $code): void
    {
        $this->database->ensureLocalesDriver();

        $this->database->transaction(function () use ($code): void {
            $this->database->lockDefaultLocale();
            $locale = $this->database->lockLocale($code);

            if ($locale->is_active || $locale->is_default) {
                throw new InvalidLocaleOperationException(
                    "Locale [{$code}] must be inactive and not the default to be deleted.",
                );
            }

            foreach ($this->contexts->definitions() as $definition) {
                $this->ensureDeletable($definition, $code);
            }

            if ($this->database->contextsAreStored()) {
                $this->database->removeLocaleFromStoredContexts($code);
            }

            $locale->delete();
        });
    }

    private function ensureDeletable(LocaleContextDefinition $definition, string $code): void
    {
        if ($definition->default === $code) {
            throw new InvalidLocaleOperationException(
                "Locale [{$code}] is the default of the [{$definition->name}] context. Choose another default for that context first.",
            );
        }

        if (! $definition->persisted && $definition->locales !== null && in_array($code, $definition->locales, true)) {
            throw new InvalidLocaleOperationException(
                "Locale [{$code}] is listed by the [{$definition->name}] context in config/localization.php. Remove it there first.",
            );
        }
    }
}
