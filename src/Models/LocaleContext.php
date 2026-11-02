<?php

declare(strict_types=1);

namespace Tipi\Localization\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A database-managed locale context.
 *
 * Memberships live in a separate table (see membershipTable()) holding
 * (context, locale) pairs. They are written by the context actions; use
 * localeCodes() to read them.
 *
 * @property string $name
 * @property bool $all_locales
 * @property ?string $default_locale
 */
class LocaleContext extends Model
{
    protected $table = 'locale_contexts';

    protected $primaryKey = 'name';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'all_locales' => 'boolean',
        ];
    }

    public function membershipTable(): string
    {
        return 'locale_context_locales';
    }

    /**
     * @return list<string>|null null when the context includes every active locale
     */
    public function localeCodes(): ?array
    {
        if ($this->all_locales) {
            return null;
        }

        return $this->getConnection()
            ->table($this->membershipTable())
            ->where('context', $this->getKey())
            ->orderBy('locale')
            ->pluck('locale')
            ->all();
    }
}
