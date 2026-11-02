<?php

declare(strict_types=1);

namespace Tipi\Localization\Support;

/**
 * @internal
 */
final class SafeRedirect
{
    /**
     * A same-site path ("/ka/wines?page=2"), or "/" for anything else.
     */
    public static function path(mixed $target): string
    {
        if (
            ! is_string($target)
            || ! str_starts_with($target, '/')
            || str_starts_with($target, '//')
            || str_contains($target, '\\')
            || preg_match('/[\x00-\x20\x7F]/', $target) === 1
        ) {
            return '/';
        }

        return $target;
    }
}
