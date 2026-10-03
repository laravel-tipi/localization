{{-- packages/laravel-localization/resources/views/filament/tables/columns/translations-column-header.blade.php --}}

@php use Tipi\Localization\Support\LocaleFlag; @endphp

<ul class="flex items-center gap-3">
    @foreach ($column->getLocales() as $code => $locale)
        <li>
            @svg (
                'flag-4x3-' . LocaleFlag::country($locale->code),
                'h-5 w-5'
            )
        </li>
    @endforeach
</ul>
