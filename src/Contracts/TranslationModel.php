<?php

declare(strict_types=1);

namespace Tipi\Localization\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property $outdated_at
 * @property $locale_code
 * @property $translationParent
 */
interface TranslationModel
{
    public static function getTranslationParentModelClass(): string;

    public function translationParent(): BelongsTo;

    public function isDefault(): bool;

    public function canBeDeleted(): bool;

    public function canBeUpdated(): bool;

    public function isOutdated(): bool;
}
