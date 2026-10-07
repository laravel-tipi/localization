<?php

declare(strict_types=1);

namespace Tipi\Localization\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Tipi\Localization\Models\Locale;

class LocalePolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return true;
    }

    public function view(Authenticatable $user, Locale $locale): bool
    {
        return true;
    }

    public function update(Authenticatable $user, Locale $locale): bool
    {
        return true;
    }

    public function activate(Authenticatable $user, Locale $locale): bool
    {
        return true;
    }

    public function deactivate(Authenticatable $user, Locale $locale): bool
    {
        return true;
    }

    public function makeDefault(Authenticatable $user, Locale $locale): bool
    {
        return true;
    }

    public function delete(Authenticatable $user, Locale $locale): bool
    {
        return true;
    }
}
