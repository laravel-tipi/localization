<?php

declare(strict_types=1);

namespace Tipi\Localization\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Tipi\Localization\Contracts\TranslationModel;
use Tipi\Localization\LocaleResolver;
use Tipi\Localization\Models\LocaleModel;

/**
 * @template TTranslation of Model&TranslationModel
 *
 * @property array<int, string> $translatable
 * @property-read Collection<int, TranslationModel> $localizedTranslations
 * @property-read Collection<int, TranslationModel> $translations
 * @property-read TranslationModel|null $defaultTranslation
 *
 * @mixin Model
 */
trait HasLocalizedTranslations
{
    protected static ?string $translationModel = null;

    /** @return array<int, string> */
    public function getTranslatable(): array
    {
        return $this->translatable;
    }

    public static function getTranslationModelClass(): string
    {
        if (static::$translationModel !== null) {
            return static::$translationModel;
        }

        static::$translationModel = static::resolveTranslationModelClass();

        return static::$translationModel;
    }

    /**
     * @return HasMany<TTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(static::getTranslationModelClass());
    }

    /**
     * @return HasOne<TTranslation, $this>
     */
    public function defaultTranslation(): HasOne
    {
        return $this->hasOne(static::getTranslationModelClass())
            ->where(
                'locale_code',
                static::getLocaleResolver()->getDefaultCode(),
            );
    }

    /**
     * Use for localized display only.
     * For application logic, use translations().
     *
     * @return HasMany<TTranslation, $this>
     */
    public function localizedTranslations(): HasMany
    {
        $localeResolver = static::getLocaleResolver();

        $localeCodes = array_filter([
            $localeResolver->getCurrentCode(),
            $localeResolver->getDefaultCode(),
        ]);

        return $this->translations()
            ->whereIn('locale_code', array_unique($localeCodes));
    }

    public function initializeHasLocalizedTranslations(): void
    {
        if (! in_array('localizedTranslations', $this->with, true)) {
            $this->with[] = 'localizedTranslations';
        }
    }

    public function getAttribute($key): mixed
    {
        if (
            is_string($key)
            && in_array($key, $this->getTranslatable(), true)
        ) {
            return $this->translated($key);
        }

        return parent::getAttribute($key);
    }

    /**
     * @return TranslationModel|null
     */
    public function translation(?string $localeCode = null): ?Model
    {
        $localeCode ??= static::getLocaleResolver()->getCurrentCode();

        if ($localeCode !== null) {
            $translation = $this->localizedTranslations->firstWhere('locale_code', $localeCode);

            if ($translation !== null) {
                return $translation;
            }
        }

        $fallbackLocale = static::getLocaleResolver()->getDefaultCode();

        if ($fallbackLocale === '' || $fallbackLocale === $localeCode) {
            return null;
        }

        return $this->localizedTranslations->firstWhere('locale_code', $fallbackLocale);
    }

    public function translated(string $field, mixed $default = null, ?string $localeCode = null): mixed
    {
        return data_get($this->translation($localeCode), $field, $default);
    }

    public function canBeTranslated(): bool
    {
        return $this->hasMissingTranslations();
    }

    public function translationExists(?string $localeCode = null): bool
    {
        $localeCode ??= static::getLocaleResolver()->getCurrentCode();

        return $this->translationExistsForLocale($localeCode);
    }

    public function translationExistsForLocale(string $localeCode): bool
    {
        return $this->translations()
            ->where('locale_code', $localeCode)
            ->exists();
    }

    public function hasMissingTranslations(): bool
    {
        $defaultCode = static::getLocaleResolver()->getDefaultCode();

        return LocaleModel::query()
            ->where('code', '!=', $defaultCode)
            ->pluck('code')
            ->contains(
                fn (string $localeCode): bool => $this->isTranslationMissing($localeCode),
            );
    }

    public function isTranslationMissing(string $localeCode): bool
    {
        return ! $this->translationExistsForLocale($localeCode);
    }

    public function translationUpdatedAt(?string $localeCode = null): ?Carbon
    {
        $localeCode ??= static::getLocaleResolver()->getCurrentCode();

        return $this->translations()
            ->where('locale_code', $localeCode)
            ->first()
            ?->updated_at;
    }

    public function hasOutdatedTranslations(): bool
    {
        return $this->translations()->whereNotNull('outdated_at')->exists();
    }

    protected static function resolveTranslationModelClass(): string
    {
        return static::class.'Translation';
    }

    protected static function getLocaleResolver(): LocaleResolver
    {
        return app(LocaleResolver::class);
    }
}
