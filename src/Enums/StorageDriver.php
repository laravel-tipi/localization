<?php

declare(strict_types=1);

namespace Tipi\Localization\Enums;

enum StorageDriver: string
{
    case Config = 'config';
    case Database = 'database';
}
