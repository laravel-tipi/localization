<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

final class DefaultLocaleNotDefinedException extends LocaleException
{
    public function __construct()
    {
        parent::__construct('Default locale not defined');
    }
}
