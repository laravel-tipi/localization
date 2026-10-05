<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

final class DefaultLocaleNotDefinedException extends LocaleException
{
    public function __construct()
    {
        parent::__construct(
            'A default locale has not been defined.',
        );
    }
}
