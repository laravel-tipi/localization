<?php

declare(strict_types=1);

namespace Tipi\Localization\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Tipi\Support\Enums\TextDirection;
use Tipi\Support\Locale as LocaleData;

/**
 * @property string $code
 * @property string $name
 * @property string $native_name
 * @property ?string $native_abbreviation
 * @property ?string $country_code
 * @property TextDirection $text_direction
 * @property int $position
 * @property bool $is_active
 * @property bool $is_default
 */
class Locale extends Model
{
    protected $table = 'locales';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    /**
     * Internal column used by the MySQL/MariaDB "one default" constraint.
     *
     * @var list<string>
     */
    protected $hidden = ['default_guard'];

    protected function casts(): array
    {
        return [
            'text_direction' => TextDirection::class,
            'position' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('code');
    }

    public function toLocale(): LocaleData
    {
        return new LocaleData(
            code: $this->code,
            name: $this->name,
            nativeName: $this->native_name,
            nativeAbbreviation: $this->native_abbreviation,
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

    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /*
     * The checks below cover the locale's own state, which is enough for
     * showing or hiding buttons. The actions also enforce context
     * invariants (for example: a context default cannot be deactivated)
     * and throw InvalidLocaleOperationException with the reason.
     */

    public function canBeActivated(): bool
    {
        return ! $this->is_active;
    }

    public function canBeDeactivated(): bool
    {
        return $this->is_active && ! $this->is_default;
    }

    public function canBeMadeDefault(): bool
    {
        return $this->is_active && ! $this->is_default;
    }

    public function canBeDeleted(): bool
    {
        return ! $this->is_active && ! $this->is_default;
    }
}
