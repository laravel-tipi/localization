<?php

declare(strict_types=1);

namespace Tipi\Localization\Tests\Fixtures;

use Tipi\Localization\Models\Locale;

final class SecondaryLocale extends Locale
{
    protected $connection = 'secondary';

    protected $table = 'languages';
}
