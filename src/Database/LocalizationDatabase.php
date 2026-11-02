<?php

declare(strict_types=1);

namespace Tipi\Localization\Database;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\UniqueConstraintViolationException;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Contexts\LocaleContextDefinition;
use Tipi\Localization\Contexts\LocaleContextValidator;
use Tipi\Localization\Enums\StorageDriver;
use Tipi\Localization\Exceptions\InvalidLocaleOperationException;
use Tipi\Localization\Exceptions\LocaleNotFoundException;
use Tipi\Localization\LocaleContexts;
use Tipi\Localization\LocaleRegistry;
use Tipi\Localization\Models\Locale;
use Tipi\Localization\Models\LocaleContext;

/**
 * Shared plumbing for the management actions: driver checks,
 * transactions on the models' own connections, row locking, cache
 * flushing and context persistence.
 *
 * @internal
 */
final readonly class LocalizationDatabase
{
    public function __construct(
        private LocalizationConfig $config,
        private LocaleRegistry $locales,
        private LocaleContexts $contexts,
        private LocaleContextValidator $validator,
    ) {}

    public function ensureLocalesDriver(): void
    {
        if ($this->config->localesDriver !== StorageDriver::Database) {
            throw new InvalidLocaleOperationException(
                'Locales can only be managed with the "database" locales driver; the current driver is "config".',
            );
        }
    }

    public function ensureContextsDriver(): void
    {
        if ($this->config->contextsDriver !== StorageDriver::Database) {
            throw new InvalidLocaleOperationException(
                'Locale contexts can only be managed with the "database" contexts driver; the current driver is "config".',
            );
        }
    }

    public function contextsAreStored(): bool
    {
        return $this->config->contextsDriver === StorageDriver::Database;
    }

    /**
     * Runs the callback in one transaction, then flushes the in-memory
     * registry and contexts so the current request sees the change.
     * Unique-constraint races surface as InvalidLocaleOperationException.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     *
     * @throws InvalidLocaleOperationException when locales and contexts are stored on different connections
     */
    public function transaction(Closure $callback): mixed
    {
        $connection = $this->connection();

        try {
            return $connection->transaction(function () use ($callback): mixed {
                $this->flush();

                return $callback();
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw new InvalidLocaleOperationException(
                'The change conflicts with a concurrent change (unique constraint violated). Please retry.',
                previous: $exception,
            );
        } finally {
            $this->flush();
        }
    }

    /**
     * Lock and return a locale row.
     *
     * @throws LocaleNotFoundException
     */
    public function lockLocale(string $code): Locale
    {
        $model = $this->config->localeModel;

        return $model::query()->lockForUpdate()->find($code) ?? throw new LocaleNotFoundException($code);
    }

    /**
     * Serialize context writes with default-locale changes: both lock the
     * current default locale row (database locales driver only).
     */
    public function lockDefaultLocale(): void
    {
        if ($this->config->localesDriver !== StorageDriver::Database) {
            return;
        }

        $model = $this->config->localeModel;

        $model::query()->where('is_default', true)->lockForUpdate()->first();
    }

    /**
     * Validate and store a context definition (row and memberships).
     */
    public function saveContext(LocaleContextDefinition $definition): LocaleContext
    {
        $this->validator->validate($definition, forStorage: true);

        $model = $this->config->contextModel;

        /** @var LocaleContext $row */
        $row = $model::query()->lockForUpdate()->find($definition->name) ?? new $model;

        $row->name = $definition->name;
        $row->all_locales = $definition->includesAllLocales();
        $row->default_locale = $definition->default;
        $row->save();

        $memberships = $row->getConnection()->table($row->membershipTable());

        $memberships->where('context', $definition->name)->delete();

        if ($definition->locales !== null) {
            $memberships->insert(array_map(
                fn (string $code): array => ['context' => $definition->name, 'locale' => $code],
                $definition->locales,
            ));
        }

        return $row;
    }

    public function deleteContext(string $name): void
    {
        $model = $this->config->contextModel;
        $instance = new $model;

        $instance->getConnection()->table($instance->membershipTable())->where('context', $name)->delete();

        $model::query()->whereKey($name)->delete();
    }

    public function removeLocaleFromStoredContexts(string $code): void
    {
        $model = $this->config->contextModel;
        $instance = new $model;

        $instance->getConnection()->table($instance->membershipTable())->where('locale', $code)->delete();
    }

    public function flush(): void
    {
        $this->locales->flush();
        $this->contexts->flush();
    }

    /**
     * The connection every management write runs on. Changes that touch
     * locales and contexts together are only atomic when both are stored
     * on the same connection, so anything else is rejected before a
     * write (there are no distributed transactions).
     */
    private function connection(): Connection
    {
        $locales = $this->config->localesDriver === StorageDriver::Database
            ? (new $this->config->localeModel)->getConnection()
            : null;

        $contexts = $this->contextsAreStored()
            ? (new $this->config->contextModel)->getConnection()
            : null;

        if ($locales !== null && $contexts !== null && $locales->getName() !== $contexts->getName()) {
            throw new InvalidLocaleOperationException(
                'Locales and locale contexts must be stored on the same database connection so they can be '
                ."changed in one transaction; the locale model uses [{$locales->getName()}] and the context "
                ."model uses [{$contexts->getName()}]. Set the same \$connection on both models.",
            );
        }

        return $locales ?? $contexts ?? throw new InvalidLocaleOperationException(
            'Nothing is stored in the database: both the locales and contexts drivers are "config".',
        );
    }
}
