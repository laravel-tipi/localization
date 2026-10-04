<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

final class UnsupportedLocaleException extends LocaleException
{
    public function __construct(string $code)
    {
        parent::__construct("LocaleModel [$code] is not supported");
    }
}
