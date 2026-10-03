<?php

declare(strict_types=1);

namespace Tipi\Localization\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Locale extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
            'is_default' => 'bool',
        ];
    }

    // helpers
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isInactive(): bool
    {
        return ! $this->isActive();
    }

    public function isDefault(): bool
    {
        return $this->is_default;
    }

    public function canBeMadeDefault(): bool
    {
        return ! $this->isDefault() && $this->isActive();
    }

    public function canBeActivated(): bool
    {
        return $this->isInactive();
    }

    public function canBeDeactivated(): bool
    {
        return $this->isActive() && ! $this->isDefault();
    }

    // scopes
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function default(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
