<?php

declare(strict_types=1);

namespace Tipi\Localization\Enums;

enum LocaleDriver: string
{
    case Database = 'database';
    case Config = 'config';
}
