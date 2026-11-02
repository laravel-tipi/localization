<?php

declare(strict_types=1);

namespace Tipi\Localization\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

final class Wine extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
