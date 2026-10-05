<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

final class LocalesNotDefinedException extends LocalizationConfigurationException
{
    public function __construct()
    {
        parent::__construct(
            'No locales have been defined in the localization configuration.',
        );
    }
}
