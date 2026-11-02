<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Tipi\Localization\Models\Locale;
use Tipi\Localization\Models\LocaleContext;
use Tipi\Localization\Policies\LocalePolicy;

function policyUser(): User
{
    return new User;
}

it('B21: denies locale and context management by default', function (string $ability, string|object $target) {
    expect(Gate::forUser(policyUser())->allows($ability, $target))->toBeFalse();
})->with([
    ['viewAny', Locale::class],
    ['create', Locale::class],
    ['update', new Locale],
    ['activate', new Locale],
    ['deactivate', new Locale],
    ['makeDefault', new Locale],
    ['reorder', Locale::class],
    ['delete', new Locale],
    ['viewAny', LocaleContext::class],
    ['update', new LocaleContext],
    ['delete', new LocaleContext],
]);

it('allows management when the manage-localization ability is granted', function () {
    Gate::define(LocalePolicy::ABILITY, fn (User $user) => true);

    expect(Gate::forUser(policyUser())->allows('makeDefault', new Locale))->toBeTrue()
        ->and(Gate::forUser(policyUser())->allows('update', new LocaleContext))->toBeTrue();
});

it('respects Gate::before callbacks', function () {
    Gate::before(fn () => true);

    expect(Gate::forUser(policyUser())->allows('delete', new Locale))->toBeTrue();
});
