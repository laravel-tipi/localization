<?php

declare(strict_types=1);

namespace Tipi\Localization;

use LogicException;
use Tipi\Localization\Config\LocalizationConfig;
use Tipi\Localization\Models\Locale;

final readonly class LocaleModelResolver
{
    public function __construct(
        private LocalizationConfig $config,
    ) {}

    /**
     * @return class-string<Locale>
     */
    public function class(): string
    {
        $model = $this->config->localeModel;

        if (! is_a($model, Locale::class, true)) {
            throw new LogicException(
                "Configured locale model [{$model}] must extend [".
                Locale::class.'].',
            );
        }

        return $model;
    }
}
