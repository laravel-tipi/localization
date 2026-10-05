<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

final class InvalidLocaleConfigurationException extends LocalizationConfigurationException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
