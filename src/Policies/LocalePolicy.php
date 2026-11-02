<?php

declare(strict_types=1);

namespace Tipi\Localization\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Tipi\Localization\Models\Locale;

/**
 * Denies everything unless the user is granted the "manage-localization"
 * gate ability:
 *
 *     Gate::define('manage-localization', fn (User $user) => $user->isAdmin());
 *
 * Replace it with your own policy through config('localization.policies.locale').
 */
class LocalePolicy
{
    public const string ABILITY = 'manage-localization';

    public function viewAny(Authenticatable $user): bool
    {
        return $this->allows($user);
    }

    public function view(Authenticatable $user, Locale $locale): bool
    {
        return $this->allows($user);
    }

    public function create(Authenticatable $user): bool
    {
        return $this->allows($user);
    }

    public function update(Authenticatable $user, Locale $locale): bool
    {
        return $this->allows($user);
    }

    public function activate(Authenticatable $user, Locale $locale): bool
    {
        return $this->allows($user);
    }

    public function deactivate(Authenticatable $user, Locale $locale): bool
    {
        return $this->allows($user);
    }

    public function makeDefault(Authenticatable $user, Locale $locale): bool
    {
        return $this->allows($user);
    }

    public function reorder(Authenticatable $user): bool
    {
        return $this->allows($user);
    }

    public function delete(Authenticatable $user, Locale $locale): bool
    {
        return $this->allows($user);
    }

    protected function allows(Authenticatable $user): bool
    {
        return Gate::forUser($user)->allows(self::ABILITY);
    }
}
