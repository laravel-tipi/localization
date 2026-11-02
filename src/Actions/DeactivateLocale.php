<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Tipi\Localization\Contexts\LocaleContextDefinition;
use Tipi\Localization\Database\LocalizationDatabase;
use Tipi\Localization\Exceptions\InvalidLocaleOperationException;
use Tipi\Localization\Exceptions\LocaleNotFoundException;
use Tipi\Localization\LocaleContexts;
use Tipi\Localization\Models\Locale;

/**
 * Deactivates a locale. Rejected for the global default and for a locale
 * that is the explicit default of a context: choose another default for
 * that context first.
 */
final readonly class DeactivateLocale
{
    public function __construct(
        private LocalizationDatabase $database,
        private LocaleContexts $contexts,
    ) {}

    /**
     * @throws LocaleNotFoundException
     * @throws InvalidLocaleOperationException
     */
    public function execute(string $code): Locale
    {
        $this->database->ensureLocalesDriver();

        return $this->database->transaction(function () use ($code): Locale {
            $this->database->lockDefaultLocale();
            $locale = $this->database->lockLocale($code);

            if (! $locale->is_active) {
                throw new InvalidLocaleOperationException("Locale [{$code}] is already inactive.");
            }

            if ($locale->is_default) {
                throw new InvalidLocaleOperationException(
                    "Locale [{$code}] is the global default locale and cannot be deactivated. Make another locale the default first.",
                );
            }

            $context = $this->contexts->definitions()->first(
                fn (LocaleContextDefinition $definition): bool => $definition->default === $code,
            );

            if ($context !== null) {
                throw new InvalidLocaleOperationException(
                    "Locale [{$code}] is the default of the [{$context->name}] context. Choose another default for that context first.",
                );
            }

            $locale->is_active = false;
            $locale->save();

            return $locale;
        });
    }
}
