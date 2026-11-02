<?php

declare(strict_types=1);

namespace Tipi\Localization\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Tipi\Localization\Models\LocaleContext;

/**
 * Denies everything unless the user is granted the "manage-localization"
 * gate ability. Replace it through config('localization.policies.context').
 */
class LocaleContextPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $this->allows($user);
    }

    public function view(Authenticatable $user, LocaleContext $context): bool
    {
        return $this->allows($user);
    }

    public function create(Authenticatable $user): bool
    {
        return $this->allows($user);
    }

    public function update(Authenticatable $user, LocaleContext $context): bool
    {
        return $this->allows($user);
    }

    public function delete(Authenticatable $user, LocaleContext $context): bool
    {
        return $this->allows($user);
    }

    protected function allows(Authenticatable $user): bool
    {
        return Gate::forUser($user)->allows(LocalePolicy::ABILITY);
    }
}
