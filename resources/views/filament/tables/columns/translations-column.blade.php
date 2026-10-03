{{-- packages/laravel-localization/resources/views/filament/tables/columns/translations-column.blade.php --}}

@php use Tipi\Localization\Contracts\TranslatableModel; @endphp

<ul class="flex gap-3 items-center px-3 py-4">
    @foreach ($column->getLocales() as $code => $locale)
        @php
            /** @var TranslatableModel $record */
            $record = $getRecord();
            $exists = $record->translationExists($locale->code);
        @endphp

        <li>
            @if ($exists)
                <x-filament::icon-button
                    icon="heroicon-m-pencil-square"
                    :tooltip="'Edit ' . $locale->name . ' translation'"
                    wire:click.stop="mountTableAction(
                    'edit_translation',
                    '{{ $record->getKey() }}',
                    { locale: '{{ $locale->code }}' }
                )"
                />
            @else
                <x-filament::icon-button
                    icon="heroicon-m-plus"
                    :tooltip="'Add ' . $locale->name . ' translation'"
                    wire:click.stop="mountTableAction(
                    'translate',
                    '{{ $record->getKey() }}',
                    { locale: '{{ $locale->code }}' }
                )"
                />
            @endif
        </li>
    @endforeach
</ul>
