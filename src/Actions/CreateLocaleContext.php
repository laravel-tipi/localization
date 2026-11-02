<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Tipi\Localization\Contexts\LocaleContextDefinition;
use Tipi\Localization\Data\CreateLocaleContextData;
use Tipi\Localization\Database\LocalizationDatabase;
use Tipi\Localization\Exceptions\InvalidLocaleContextException;
use Tipi\Localization\Exceptions\InvalidLocaleOperationException;
use Tipi\Localization\LocaleContexts;
use Tipi\Localization\Models\LocaleContext;

final readonly class CreateLocaleContext
{
    public function __construct(
        private LocalizationDatabase $database,
        private LocaleContexts $contexts,
    ) {}

    /**
     * @throws InvalidLocaleOperationException when the context already exists (or is declared in config)
     * @throws InvalidLocaleContextException when the definition breaks an invariant
     */
    public function execute(CreateLocaleContextData $data): LocaleContext
    {
        $this->database->ensureContextsDriver();

        return $this->database->transaction(function () use ($data): LocaleContext {
            $this->database->lockDefaultLocale();

            if ($this->contexts->has($data->name)) {
                throw new InvalidLocaleOperationException(
                    "Locale context [{$data->name}] already exists. Use UpdateLocaleContext to change it.",
                );
            }

            return $this->database->saveContext(new LocaleContextDefinition(
                name: $data->name,
                locales: $data->locales,
                default: $data->default,
                required: false,
                persisted: true,
            ));
        });
    }
}
