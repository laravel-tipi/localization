<?php

declare(strict_types=1);

namespace Tipi\Localization\Filament\Tables\Columns;

use Closure;
use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Mcamara\LaravelLocalization\Exceptions\SupportedLocalesNotDefined;
use Tipi\Localization\Contracts\TranslatableModel;
use Tipi\Localization\LocaleResolver;
use Tipi\Localization\Models\LocaleModel;

class TranslationsColumn extends Column
{
    protected string $view = 'tipi-localization::filament.tables.columns.translations-column';

    protected string $headerView = 'tipi-localization::filament.tables.columns.translations-column-header';

    public function getHeaderView(): string
    {
        return $this->headerView;
    }

    protected (Model&TranslatableModel)|Closure|null $translatableRecord = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->disabledClick()
            ->label(
                fn () => new HtmlString(
                    view(
                        $this->getHeaderView(),
                        [
                            'column' => $this,
                        ],
                    )->render(),
                ),
            );
    }

    public function translatable((Model&TranslatableModel)|Closure $record): static
    {
        $this->translatableRecord = $record;

        return $this;
    }

    /**
     * @return Collection<string, LocaleModel>
     *
     * @throws SupportedLocalesNotDefined
     */
    public function getLocales(): Collection
    {
        $localeResolver = resolve(LocaleResolver::class);

        return $localeResolver
            ->getSupportedLocales()
            ->reject(
                fn (LocaleModel $locale): bool => $locale->getKey() === $localeResolver->getDefaultId(),
            );
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
}
