<?php

declare(strict_types=1);

namespace Tipi\Localization\Filament\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use LogicException;
use Tipi\Localization\Actions\CreateTranslation;
use Tipi\Localization\Contracts\TranslatableModel;
use Tipi\Localization\Contracts\TranslationModel;
use Tipi\Localization\Exceptions\TranslationAlreadyExistsException;
use Tipi\Localization\LocaleResolver;
use Tipi\Localization\Support\Filament\FilamentValidator;

class TranslateAction extends Action
{
    protected (Model&TranslatableModel)|Closure|null $translatableRecord = null;

    protected string|Closure|null $localeCode = null;

    protected string|Closure|null $selectedLocaleCode = null;

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

    public function translatable((Model&TranslatableModel)|Closure $record): static
    {
        $this->translatableRecord = $record;

        return $this;
    }

    public function localeCode(string|Closure|null $localeCode): static
    {
        $this->localeCode = $localeCode;

        return $this;
    }

    public function getRecordTitle(): string
    {
        return $this->evaluate($this->recordTitle)
            ?? (string) $this->getTranslatableRecord()->getKey();
    }

    public function getTranslatableRecord(): (Model&TranslatableModel)|null
    {
        /** @var (Model&TranslatableModel) $record */
        $record = $this->translatableRecord !== null
            ? $this->evaluate($this->translatableRecord)
            : $this->getRecord();

        if (! $record instanceof TranslatableModel) {
            return null;
        }

        return $record;
    }

    public function getLocaleCode(): ?string
    {
        return $this->evaluate($this->localeCode) ?? null;
    }

    public function getSelectedLocaleCode(): ?string
    {
        if ($this->selectedLocaleCode !== null) {
            return $this->evaluate($this->selectedLocaleCode);
        }

        return null;
    }

    public function getDefaultTranslation(): Model&TranslationModel
    {
        $record = $this->getTranslatableRecord();

        if ($record === null) {
            throw new LogicException('Translatable record must not be null.');
        }

        return $record->defaultTranslation;
    }

    protected function getLocaleResolver(): LocaleResolver
    {
        return resolve(LocaleResolver::class);
    }

    protected function getLocaleSchema(): array
    {
        if ($this->getLocaleCode() !== null) {
            return [];
        }

        return [
            Flex::make([
                Select::make('locale_code')
                    ->label('Language')
                    ->required()
                    ->live()
                    ->options(function (): array {
                        $parent = $this->getTranslatableRecord();

                        if ($parent === null) {
                            return [];
                        }

                        return $this->getLocaleResolver()
                            ->getMissingLocales($parent)
                            ->pluck('name', 'code')
                            ->all();
                    })
                    ->searchable(['code', 'name', 'native_name'])
                    ->afterStateUpdated(function (?string $state): void {
                        $this->selectedLocaleCode = $state;
                    }),
            ])
                ->maxWidth(Width::TwoExtraSmall),
        ];
    }

    public function getModalHeading(): string
    {
        $record = $this->getTranslatableRecord();

        $localeCode = $this->getLocaleCode() ?? $this->getSelectedLocaleCode();

        if ($localeCode === null) {
            return 'Translate';
        }

        $locale = $this->getLocaleResolver()->getLocale($localeCode);

        $title = $this->getRecordTitle();

        return "Translate $title to $locale->name";
    }

    public function getModalSubmitActionLabel(): string
    {
        return 'Create Translation';
    }

    public function getTranslationButtonLabel(): string
    {
        if ($this->getLocaleCode() !== null) {
            $locale = $this->getLocaleResolver()->getLocale($this->getLocaleCode());

            return "Translate to $locale->name";
        }

        return 'Translate';
    }

    public function shouldBeVisible(): bool
    {
        $record = $this->getTranslatableRecord();

        if ($record === null) {
            return false;
        }

        if ($localeCode = $this->getLocaleCode()) {
            return $record->isTranslationMissing($localeCode);
        }

        return $record->hasMissingTranslations();
    }

    public function getDefaultTranslationSectionLabel(): string
    {
        $locale = $this->getLocaleResolver()->getDefaultLocale();

        return "$locale->name";
    }

    public function getTranslationSectionLabel(): string
    {
        $localeCode = $this->getLocaleCode() ?? $this->getSelectedLocaleCode();

        if ($localeCode === null) {
            return 'Translation';
        }

        return $this->getLocaleResolver()
            ->getLocale($localeCode)
            ->name;
    }

    public function getTranslationButtonIcon(): string
    {
        return 'heroicon-o-language';
    }

    public static function getDefaultName(): ?string
    {
        return 'translate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(fn (): string => $this->getTranslationButtonLabel())
            ->color('primary')
            ->icon(fn () => $this->getTranslationButtonIcon())
            ->tableIcon(fn () => $this->getTranslationButtonIcon())
            ->groupedIcon(fn () => $this->getTranslationButtonIcon())
            ->authorize(
                fn (): bool => Gate::allows(
                    'translate',
                    $this->getTranslatableRecord(),
                ),
            )
            ->visible(fn (): bool => $this->shouldBeVisible())
            ->modalHeading(fn (): string => $this->getModalHeading())
            ->modalSubmitActionLabel(fn (): string => $this->getModalSubmitActionLabel())
            ->modalWidth(Width::SevenExtraLarge)
            ->schema(fn (): array => [
                ...$this->getLocaleSchema(),
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
                    $parent = $this->getTranslatableRecord();

                    $localeCode = $this->getLocaleCode()
                        ?? (string) $data['locale_code'];

                    try {
                        resolve(CreateTranslation::class)->execute(
                            parent: $parent,
                            attributes: $data['translation'],
                            localeCode: $localeCode,
                        );
                    } catch (TranslationAlreadyExistsException $exception) {
                        FilamentValidator::fail(
                            field: 'locale_code',
                            message: $exception->getMessage(),
                            statePath: 'mountedActions.0.data',
                        );
                    }

                    $locale = $this->getLocaleResolver()->getLocale($localeCode);

                    Notification::make()
                        ->title("Translated to $locale->name")
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
     * @return array<Component>
     */
    protected function getTranslationSchema(): array
    {
        $defaultTranslation = $this->getDefaultTranslation();

        return collect($this->translationSchema)
            ->map(
                function (Closure $factory, string $attribute) use ($defaultTranslation): Component {
                    $translationField = $factory()
                        ->hiddenLabel()
                        ->statePath("translation.$attribute");

                    $defaultField = TextEntry::make("default_translation.$attribute")
                        ->hiddenLabel()
                        ->state(
                            data_get($defaultTranslation, $attribute),
                        );

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
