<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Tipi\Localization\Database\LocalizationDatabase;
use Tipi\Localization\Exceptions\InvalidLocaleOperationException;
use Tipi\Localization\Exceptions\LocaleContextNotFoundException;
use Tipi\Localization\LocaleContexts;

/**
 * Deletes a stored context. Contexts declared in config (including the
 * default context) are required by application code and cannot be deleted.
 */
final readonly class DeleteLocaleContext
{
    public function __construct(
        private LocalizationDatabase $database,
        private LocaleContexts $contexts,
    ) {}

    /**
     * @throws LocaleContextNotFoundException
     * @throws InvalidLocaleOperationException
     */
    public function execute(string $name): void
    {
        $this->database->ensureContextsDriver();

        $this->database->transaction(function () use ($name): void {
            $definition = $this->contexts->definition($name);

            if ($definition->required) {
                throw new InvalidLocaleOperationException(
                    "Locale context [{$name}] is declared in config/localization.php and cannot be deleted.",
                );
            }

            $this->database->deleteContext($name);
        });
    }
}
