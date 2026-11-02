<?php

declare(strict_types=1);

namespace Tipi\Localization\Actions;

use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Database\LocalizationDatabase;
use Tipi\Localization\Exceptions\LocaleNotFoundException;
use Tipi\Localization\Models\Locale;

/**
 * Sets the display order. The given codes come first, in order; any
 * other locales keep their relative order after them.
 */
final readonly class ReorderLocales
{
    public function __construct(
        private LocalizationDatabase $database,
        private LocalizationConfig $config,
    ) {}

    /**
     * @param  list<string>  $codes
     *
     * @throws LocaleNotFoundException
     */
    public function execute(array $codes): void
    {
        $this->database->ensureLocalesDriver();

        $this->database->transaction(function () use ($codes): void {
            $model = $this->config->localeModel;

            $locales = $model::query()->ordered()->lockForUpdate()->get()->keyBy('code');

            foreach ($codes as $code) {
                if (! $locales->has($code)) {
                    throw new LocaleNotFoundException($code);
                }
            }

            $order = [...array_values(array_unique($codes)), ...$locales->keys()->diff($codes)->values()->all()];

            foreach ($order as $position => $code) {
                /** @var Locale $locale */
                $locale = $locales->get($code);

                if ($locale->position !== $position) {
                    $locale->position = $position;
                    $locale->save();
                }
            }
        });
    }
}
