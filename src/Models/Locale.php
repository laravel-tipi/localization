<?php

declare(strict_types=1);

namespace Tipi\Localization\Models;

use Illuminate\Database\Eloquent\Model;
use Tipi\Support\Enums\TextDirection;
use Tipi\Support\Locale as LocaleData;

class Locale extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    /**
     * @var null|string
     */
    protected $table = 'locales';

    protected function casts(): array
    {
        return [
            'text_direction' => TextDirection::class,
            'is_active' => 'bool',
            'is_default' => 'bool',
        ];
    }

    // helpers
    public function toLocale(): LocaleData
    {
        return new LocaleData(
            code: $this->code,
            name: $this->name,
            nativeName: $this->native_name,
            countryCode: $this->country_code,
            textDirection: $this->text_direction,
            active: $this->is_active,
            default: $this->is_default,
        );
    }

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
}
