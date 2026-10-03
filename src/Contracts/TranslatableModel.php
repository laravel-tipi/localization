<?php

declare(strict_types=1);

namespace Tipi\Localization\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property $defaultTranslation
 */
interface TranslatableModel
{
    public function getTranslatable(): array;

    public static function getTranslationModelClass(): string;

    public function translations(): HasMany;

    public function localizedTranslations(): HasMany;

    public function defaultTranslation(): HasOne;

    public function translation(?string $localeCode = null): ?Model;

    public function translated(string $field, mixed $default = null, ?string $localeCode = null): mixed;

    public function translationExists(?string $localeCode = null): bool;

    public function translationExistsForLocale(string $localeCode): bool;

    public function hasOutdatedTranslations(): bool;

    public function hasMissingTranslations(): bool;

    public function canBeTranslated(): bool;

    public function isTranslationMissing(string $localeCode): bool;

    public function translationUpdatedAt(?string $localeCode = null): ?Carbon;
}
