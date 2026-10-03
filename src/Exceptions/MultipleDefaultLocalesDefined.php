<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

final class MultipleDefaultLocalesDefined extends LocaleException
{
    public function __construct()
    {
        parent::__construct('Multiple default locales defined.');
    }
}
