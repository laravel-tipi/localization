<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

use LogicException;

final class InvalidLocaleConfigurationException extends LogicException implements LocalizationException
{
    public function __construct(string $message)
    {
        parent::__construct("Invalid localization configuration: {$message}");
    }
}
