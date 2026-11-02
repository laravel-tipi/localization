<?php

declare(strict_types=1);

namespace Tipi\Localization\Tests\Fixtures;

use Tipi\Localization\Facades\Localization;

final class WineController
{
    public function show(Wine $wine): string
    {
        return 'wine:'.Localization::currentCode().':'.$wine->slug;
    }

    public function vintage(string $region, int $year): string
    {
        return 'vintage:'.Localization::currentCode().":{$region}:{$year}";
    }

    public function review(Wine $wine, int $review): string
    {
        return 'review:'.Localization::currentCode().":{$wine->slug}:{$review}";
    }
}
