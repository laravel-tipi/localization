<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Contexts\LocaleContextDefinition;
use Tipi\Localization\Database\LocalizationDatabase;
use Tipi\Localization\Exceptions\InvalidLocaleOperationException;
use Tipi\Localization\Exceptions\LocaleNotFoundException;
use Tipi\Localization\LocaleContexts;
use Tipi\Localization\Models\Locale;

/**
 * Makes an active locale the global default.
 *
 * Every context must contain the global default. With database contexts
 * the new default is added to each context that lists locales, in the
 * same transaction. With config contexts the change is rejected when a
 * context does not list it (update the config first).
 */
final readonly class MakeLocaleDefault
{
    public function __construct(
        private LocalizationDatabase $database,
        private LocaleContexts $contexts,
        private LocalizationConfig $config,
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
                throw new InvalidLocaleOperationException(
                    "Locale [{$code}] is inactive and cannot be the default. Activate it first.",
                );
            }

            if ($locale->is_default) {
                throw new InvalidLocaleOperationException("Locale [{$code}] is already the default locale.");
            }

            $missing = $this->contexts->definitions()->reject(
                fn (LocaleContextDefinition $definition): bool => $definition->includes($code),
            );

            if ($missing->isNotEmpty() && ! $this->database->contextsAreStored()) {
                throw new InvalidLocaleOperationException(
                    "Locale [{$code}] cannot be the default because these contexts do not include it: "
                    .$missing->keys()->implode(', ').'. Add it to their "locales" in config/localization.php first.',
                );
            }

            $model = $this->config->localeModel;

            $model::query()->whereKeyNot($code)->where('is_default', true)->update(['is_default' => false]);

            $locale->is_default = true;
            $locale->save();

            $this->database->flush();

            foreach ($missing as $definition) {
                $this->database->saveContext(new LocaleContextDefinition(
                    name: $definition->name,
                    locales: [...$definition->locales ?? [], $code],
                    default: $definition->default,
                    required: $definition->required,
                    persisted: true,
                ));
            }

            return $locale;
        });
    }
}
