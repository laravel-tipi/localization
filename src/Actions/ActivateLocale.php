<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Tipi\Localization\Database\LocalizationDatabase;
use Tipi\Localization\Exceptions\InvalidLocaleOperationException;
use Tipi\Localization\Exceptions\InvalidLocalizedRouteException;
use Tipi\Localization\Exceptions\LocaleNotFoundException;
use Tipi\Localization\Models\Locale;
use Tipi\Localization\Routing\LocalizedRoutes;

/**
 * Activates a locale. It becomes available in every context that lists
 * it, and in every "*" context.
 */
final readonly class ActivateLocale
{
    public function __construct(
        private LocalizationDatabase $database,
        private LocalizedRoutes $routes,
    ) {}

    /**
     * @throws LocaleNotFoundException
     * @throws InvalidLocaleOperationException
     * @throws InvalidLocalizedRouteException when the code equals a static localized route segment
     */
    public function execute(string $code): Locale
    {
        $this->database->ensureLocalesDriver();
        $this->routes->ensureNotReserved($code);

        return $this->database->transaction(function () use ($code): Locale {
            $locale = $this->database->lockLocale($code);

            if ($locale->is_active) {
                throw new InvalidLocaleOperationException("Locale [{$code}] is already active.");
            }

            $locale->is_active = true;
            $locale->save();

            return $locale;
        });
    }
}
