<?php

declare(strict_types=1);

namespace Tipi\Localization\Exceptions;

use RuntimeException;

final class DefaultLocaleMissingException extends RuntimeException implements LocalizationException
{
    public function __construct()
    {
        parent::__construct(
            'No default locale exists. With the "database" locales driver, create '
            .'the first locale with the CreateLocale action (it becomes the default) '
            .'or seed the locales table with one row where is_default is true.',
        );
    }
}
