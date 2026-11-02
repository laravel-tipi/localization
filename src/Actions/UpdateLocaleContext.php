<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Tipi\Localization\Contexts\LocaleContextDefinition;
use Tipi\Localization\Data\UpdateLocaleContextData;
use Tipi\Localization\Database\LocalizationDatabase;
use Tipi\Localization\Exceptions\InvalidLocaleContextException;
use Tipi\Localization\Exceptions\LocaleContextNotFoundException;
use Tipi\Localization\LocaleContexts;
use Tipi\Localization\Models\LocaleContext;

/**
 * Replaces a context's locales and default. Contexts declared in config
 * are stored on their first update; from then on the stored definition
 * is used.
 */
final readonly class UpdateLocaleContext
{
    public function __construct(
        private LocalizationDatabase $database,
        private LocaleContexts $contexts,
    ) {}

    /**
     * @throws LocaleContextNotFoundException
     * @throws InvalidLocaleContextException
     */
    public function execute(string $name, UpdateLocaleContextData $data): LocaleContext
    {
        $this->database->ensureContextsDriver();

        return $this->database->transaction(function () use ($name, $data): LocaleContext {
            $this->database->lockDefaultLocale();

            $current = $this->contexts->definition($name);

            return $this->database->saveContext(new LocaleContextDefinition(
                name: $name,
                locales: $data->locales,
                default: $data->default,
                required: $current->required,
                persisted: true,
            ));
        });
    }
}
