<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

final class UnsupportedLocale extends LocaleException
{
    public function __construct(string $code)
    {
        parent::__construct("Locale [$code] is not supported");
    }
}
