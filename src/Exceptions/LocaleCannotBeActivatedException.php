<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

final class LocaleCannotBeActivatedException extends LocaleException
{
    public function __construct(string $code)
    {
        parent::__construct("Locale [$code] cannot be activated.");
    }
}
