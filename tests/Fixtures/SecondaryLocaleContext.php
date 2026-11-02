<?php

declare(strict_types=1);

namespace Tipi\Localization\Tests\Fixtures;

use Tipi\Localization\Models\LocaleContext;

final class SecondaryLocaleContext extends LocaleContext
{
    protected $connection = 'secondary';
}
