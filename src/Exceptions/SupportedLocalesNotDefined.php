<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

final class SupportedLocalesNotDefined extends LocaleException
{
    public function __construct()
    {
        parent::__construct('Supported locales not defined');
    }
}
