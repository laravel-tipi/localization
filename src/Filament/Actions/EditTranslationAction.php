<?php

declare(strict_types=1);

namespace Tipi\Localization\Filament\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use LogicException;
use Tipi\Localization\Actions\UpdateTranslation;
use Tipi\Localization\Contracts\TranslatableModel;
use Tipi\Localization\Contracts\TranslationModel;
use Tipi\Localization\LocaleResolver;

class EditTranslationAction extends Action
{
    protected (Model&TranslatableModel)|Closure|null $translatableRecord = null;

    protected (Model&TranslationModel)|Closure|null $translationRecord = null;

    /**
     * @var array<string, Closure>
     */
    protected array $translationSchema = [];

    protected string|Closure|null $recordTitle = null;

    public function recordTitle(string|Closure|null $title): static
    {
        $this->recordTitle = $title;

        return $this;
    }

    public static function getDefaultName(): ?string
    {
        return 'edit_translation';
    }

    public function translatable((Model&TranslatableModel)|Closure $record): static
    {
        $this->translatableRecord = $record;

        return $this;
    }

    public function translation(
        (Model&TranslationModel)|Closure $record,
    ): static {
        $this->translationRecord = $record;

        return $this;
    }

    public function getRecordTitle(): string
    {
        return $this->evaluate($this->recordTitle)
            ?? (string) $this->getTranslatableRecord()?->getKey();
    }

    protected function getLocaleResolver(): LocaleResolver
    {
        return resolve(LocaleResolver::class);
    }

    public function getTranslatableRecord(): (Model&TranslatableModel)|null
    {
        if ($this->translatableRecord !== null) {
            $record = $this->evaluate($this->translatableRecord);

            return $record instanceof TranslatableModel
                ? $record
                : null;
        }

        $translation = $this->getTranslationRecord();

        if ($translation === null) {
            return null;
        }

        $parent = $translation->translationParent;

        /** @var (Model&TranslatableModel)|null $parent */
        return $parent instanceof TranslatableModel
            ? $parent
            : null;
    }

    public function getTranslationRecord(): (Model&TranslationModel)|null
    {
        if ($this->translationRecord !== null) {

            $record = $this->evaluate($this->translationRecord);

            return $record instanceof TranslationModel
                ? $record
                : null;
        }
        $record = $this->getRecord();

        /** @var (Model&TranslationModel) $record */
        return $record instanceof TranslationModel
        ? $record
        : null;
    }

    public function getDefaultTranslation(): Model&TranslationModel
    {
        $record = $this->getTranslatableRecord();

        if ($record === null) {
            throw new LogicException('Translatable record must not be null.');
        }

        /** @var (Model&TranslationModel) $translation */
        $translation = $record->defaultTranslation;

        if (! $translation instanceof TranslationModel) {
            throw new LogicException(
                'The translatable record does not have a default translation.',
            );
        }

        return $translation;
    }

    public function getLocaleCode(): string
    {
        $translation = $this->getTranslationRecord();

        if ($translation === null) {
            throw new LogicException('Translation record must not be null.');
        }

        return $translation->locale_code;
    }

    public function getModalHeading(): string
    {
        $locale = $this->getLocaleResolver()
            ->getLocale($this->getLocaleCode());

        return "Edit {$this->getRecordTitle()} $locale->name Translation";
    }

    public function getTranslationSectionLabel(): string
    {
        return $this->getLocaleResolver()
            ->getLocale($this->getLocaleCode())
            ->name;
    }

    public function getDefaultTranslationSectionLabel(): string
    {
        return $this->getLocaleResolver()
            ->getDefaultLocale()
            ->name;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Edit Translation')
            ->icon('heroicon-c-pencil-square')
            ->tableIcon('heroicon-c-pencil-square')
            ->color('primary')
            ->authorize(
                function (Model $record): bool {
                    if ($record instanceof TranslatableModel) {
                        return Gate::allows('translate', $record);
                    }

                    if ($record instanceof TranslationModel) {
                        $parent = $record->translationParent;

                        return $parent instanceof TranslatableModel
                            && Gate::allows('translate', $parent);
                    }

                    return false;
                },
            )
            ->modalHeading(fn (): string => $this->getModalHeading())
            ->modalSubmitActionLabel('Save Translation')
            ->modalWidth(Width::SevenExtraLarge)
            ->fillForm(
                fn (): array => [
                    'translation' => $this->getTranslationFormData(),
                ],
            )
            ->schema(fn (): array => [
                Group::make()
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextEntry::make('defaultHeading')
                                    ->hiddenLabel()
                                    ->state(fn (): string => $this->getDefaultTranslationSectionLabel())
                                    ->size(TextSize::Medium),
                                TextEntry::make('translationHeading')
                                    ->hiddenLabel()
                                    ->state(fn (): string => $this->getTranslationSectionLabel())
                                    ->size(TextSize::Medium),
                            ]),
                        ...$this->getTranslationSchema(),
                    ]),
            ])
            ->action(
                function (array $data): void {
                    $translation = $this->getTranslationRecord();

                    if ($translation === null) {
                        throw new LogicException('Translation record must not be null.');
                    }

                    resolve(UpdateTranslation::class)->execute(
                        translation: $translation,
                        attributes: $data['translation'],
                    );

                    Notification::make()
                        ->title('Translation updated')
                        ->success()
                        ->send();
                });
    }

    /**
     * @param  array<string, Closure>  $schema
     */
    public function translationSchema(array $schema): static
    {
        $this->translationSchema = $schema;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getTranslationFormData(): array
    {
        $translation = $this->getTranslationRecord();

        return collect(array_keys($this->translationSchema))
            ->mapWithKeys(
                fn (string $attribute): array => [
                    $attribute => data_get($translation, $attribute),
                ],
            )
            ->all();
    }

    /**
     * @return array<Component>
     */
    protected function getTranslationSchema(): array
    {
        $defaultTranslation = $this->getDefaultTranslation();

        return collect($this->translationSchema)
            ->map(
                function (
                    Closure $factory,
                    string $attribute,
                ) use ($defaultTranslation): Component {
                    $defaultField = TextEntry::make(
                        "default_translation.$attribute",
                    )
                        ->hiddenLabel()
                        ->state(
                            data_get($defaultTranslation, $attribute),
                        );

                    $translationField = $factory()
                        ->hiddenLabel()
                        ->statePath("translation.$attribute");

                    return Grid::make()
                        ->schema([
                            $defaultField,
                            $translationField,
                        ]);
                },
            )
            ->values()
            ->all();
    }
}
