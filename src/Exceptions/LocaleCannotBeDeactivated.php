<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

final class LocaleCannotBeDeactivated extends LocaleException
{
    public function __construct(string $code)
    {
        parent::__construct("LocaleModel [$code] cannot be deactivated.");
    }
}
