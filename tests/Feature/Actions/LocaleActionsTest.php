<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Tipi\Localization\Actions\ActivateLocale;
use Tipi\Localization\Actions\CreateLocale;
use Tipi\Localization\Actions\DeactivateLocale;
use Tipi\Localization\Actions\DeleteLocale;
use Tipi\Localization\Actions\MakeLocaleDefault;
use Tipi\Localization\Actions\ReorderLocales;
use Tipi\Localization\Actions\UpdateLocale;
use Tipi\Localization\Actions\UpdateLocaleContext;
use Tipi\Localization\Data\CreateLocaleData;
use Tipi\Localization\Data\UpdateLocaleContextData;
use Tipi\Localization\Data\UpdateLocaleData;
use Tipi\Localization\Exceptions\InvalidLocaleContextException;
use Tipi\Localization\Exceptions\InvalidLocaleOperationException;
use Tipi\Localization\Exceptions\LocaleNotFoundException;
use Tipi\Localization\Facades\Localization;
use Tipi\Localization\Models\Locale;
use Tipi\Support\Enums\TextDirection;

function useDatabaseContexts(array $contexts): void
{
    test()->configureLocalization(['contexts_driver' => 'database', 'contexts' => $contexts]);
}

describe('creating', function () {
    beforeEach(fn () => $this->configureLocalization(['locales_driver' => 'database']));

    it('makes the first locale the active default and appends later ones inactive', function () {
        $en = resolve(CreateLocale::class)->execute(new CreateLocaleData('en', 'English', 'English', 'EN', 'GB'));
        $ka = resolve(CreateLocale::class)->execute(new CreateLocaleData('ka', 'Georgian', 'ქართული'));
        $ar = resolve(CreateLocale::class)->execute(new CreateLocaleData('ar', 'Arabic', 'العربية', textDirection: TextDirection::Rtl, active: true));

        expect($en)->toBeInstanceOf(Locale::class)
            ->and([$en->is_active, $en->is_default, $en->position])->toBe([true, true, 0])
            ->and([$ka->is_active, $ka->is_default, $ka->position])->toBe([false, false, 1])
            ->and([$ar->is_active, $ar->position, $ar->text_direction])->toBe([true, 2, TextDirection::Rtl])
            ->and(Localization::locales()->all()->keys()->all())->toBe(['en', 'ka', 'ar'])
            ->and(Localization::locales()->activeCodes())->toBe(['en', 'ar']);
    });

    it('rejects duplicate codes', function () {
        resolve(CreateLocale::class)->execute(new CreateLocaleData('en', 'English', 'English'));
        resolve(CreateLocale::class)->execute(new CreateLocaleData('en', 'English', 'English'));
    })->throws(InvalidLocaleOperationException::class, 'Locale [en] already exists.');

    it('B14: stores the longest supported code', function () {
        resolve(CreateLocale::class)->execute(new CreateLocaleData('yue-Hant-001', 'Cantonese', '粵語'));

        expect(Locale::query()->find('yue-Hant-001'))->not->toBeNull();
    });
});

it('updates metadata only', function () {
    $this->useDatabaseLocales(['en', 'ka']);

    $locale = resolve(UpdateLocale::class)->execute('en', new UpdateLocaleData('English (UK)', 'English', 'EN', 'GB', TextDirection::Ltr));

    expect($locale->name)->toBe('English (UK)')
        ->and([$locale->is_active, $locale->is_default])->toBe([true, true])
        ->and(Localization::locales()->get('en')->name)->toBe('English (UK)');
});

it('activates and deactivates locales, refreshing the registry', function () {
    $this->useDatabaseLocales(['en', 'ka'], inactive: ['ka']);

    expect(Localization::locales()->activeCodes())->toBe(['en']);

    resolve(ActivateLocale::class)->execute('ka');
    expect(Localization::locales()->activeCodes())->toBe(['en', 'ka']);

    resolve(DeactivateLocale::class)->execute('ka');
    expect(Localization::locales()->activeCodes())->toBe(['en']);
});

it('rejects invalid state changes with an explanation', function (string $action, string $code, string $message) {
    $this->useDatabaseLocales(['en', 'ka', 'ru'], inactive: ['ru']);

    expect(fn () => resolve($action)->execute($code))->toThrow(InvalidLocaleOperationException::class, $message);
})->with([
    'activate active' => [ActivateLocale::class, 'ka', 'already active'],
    'deactivate inactive' => [DeactivateLocale::class, 'ru', 'already inactive'],
    'deactivate default' => [DeactivateLocale::class, 'en', 'global default locale and cannot be deactivated'],
    'default inactive' => [MakeLocaleDefault::class, 'ru', 'is inactive and cannot be the default'],
    'default again' => [MakeLocaleDefault::class, 'en', 'already the default'],
    'delete active' => [DeleteLocale::class, 'ka', 'must be inactive and not the default'],
]);

it('throws LocaleNotFoundException for unknown codes', function (string $action) {
    $this->useDatabaseLocales(['en']);

    resolve($action)->execute('fr');
})->with([ActivateLocale::class, DeactivateLocale::class, MakeLocaleDefault::class, DeleteLocale::class])
    ->throws(LocaleNotFoundException::class, 'Locale [fr] does not exist.');

it('refuses to manage locales with the config driver', function () {
    resolve(ActivateLocale::class)->execute('en');
})->throws(InvalidLocaleOperationException::class, '"database" locales driver');

describe('default locale and contexts', function () {
    it('changes the default and keeps exactly one', function () {
        $this->useDatabaseLocales(['en', 'ka']);

        resolve(MakeLocaleDefault::class)->execute('ka');

        expect(DB::table('locales')->where('is_default', true)->pluck('code')->all())->toBe(['ka'])
            ->and(Localization::locales()->defaultCode())->toBe('ka');
    });

    it('adds the new default to every stored or declared context that lists locales, in one transaction', function () {
        $this->useDatabaseLocales(['en', 'ka', 'ru']);
        useDatabaseContexts(['admin' => ['locales' => ['en', 'ka'], 'default' => 'en'], 'shop' => ['locales' => ['en']]]);
        resolve(UpdateLocaleContext::class)->execute('shop', new UpdateLocaleContextData(['en']));

        resolve(MakeLocaleDefault::class)->execute('ru');

        expect(Localization::context('admin')->codes())->toBe(['en', 'ka', 'ru'])
            ->and(Localization::context('admin')->defaultCode())->toBe('en')
            ->and(Localization::context('shop')->codes())->toBe(['en', 'ru'])
            ->and(Localization::context('web')->includesAllLocales())->toBeTrue();
    });

    it('rejects the change when a config context does not include the new default', function () {
        $this->useDatabaseLocales(['en', 'ka', 'ru']);
        $this->configureLocalization(['contexts' => ['admin' => ['locales' => ['en', 'ka']]]]);

        expect(fn () => resolve(MakeLocaleDefault::class)->execute('ru'))
            ->toThrow(InvalidLocaleOperationException::class, 'these contexts do not include it: admin');

        expect(Localization::locales()->defaultCode())->toBe('en');
    });

    it('allows it when every config context includes the new default', function () {
        $this->useDatabaseLocales(['en', 'ka', 'ru']);
        $this->configureLocalization(['contexts' => ['admin' => ['locales' => ['en', 'ka']]]]);

        resolve(MakeLocaleDefault::class)->execute('ka');

        expect(Localization::context('admin')->defaultCode())->toBe('ka');
    });

    it('rolls back the default change when a context cannot be updated', function () {
        $this->useDatabaseLocales(['en', 'ka', 'ru']);
        useDatabaseContexts(['admin' => ['locales' => ['en', 'ka'], 'default' => 'xx']]);

        expect(fn () => resolve(MakeLocaleDefault::class)->execute('ru'))->toThrow(InvalidLocaleContextException::class, 'not one of its locales');

        expect(DB::table('locales')->where('is_default', true)->value('code'))->toBe('en')
            ->and(DB::table('locale_contexts')->count())->toBe(0);
    });

    it('rejects deactivating a locale that is a context default', function () {
        $this->useDatabaseLocales(['en', 'ka']);
        $this->configureLocalization(['contexts' => ['admin' => ['locales' => ['en', 'ka'], 'default' => 'ka']]]);

        resolve(DeactivateLocale::class)->execute('ka');
    })->throws(InvalidLocaleOperationException::class, 'default of the [admin] context');
});

describe('deleting', function () {
    it('deletes an inactive locale and its stored memberships', function () {
        $this->useDatabaseLocales(['en', 'ka', 'ru']);
        useDatabaseContexts([]);
        resolve(UpdateLocaleContext::class)->execute('web', new UpdateLocaleContextData(['en', 'ru']));
        resolve(DeactivateLocale::class)->execute('ru');

        resolve(DeleteLocale::class)->execute('ru');

        expect(Localization::locales()->find('ru'))->toBeNull()
            ->and(DB::table('locale_context_locales')->pluck('locale')->all())->toBe(['en']);
    });

    it('rejects deleting a locale listed by a config context', function () {
        $this->useDatabaseLocales(['en', 'ru'], inactive: ['ru']);
        $this->configureLocalization(['contexts' => ['admin' => ['locales' => ['en', 'ru']]]]);

        resolve(DeleteLocale::class)->execute('ru');
    })->throws(InvalidLocaleOperationException::class, 'Remove it there first');
});

it('reorders locales, keeping unlisted ones after', function () {
    $this->useDatabaseLocales(['en', 'ka', 'ru', 'de']);

    resolve(ReorderLocales::class)->execute(['ru', 'ka']);

    expect(Localization::locales()->all()->keys()->all())->toBe(['ru', 'ka', 'en', 'de']);
});

it('B20: orders database locales deterministically by position, then code', function () {
    $this->useDatabaseLocales(['en']);
    DB::table('locales')->insert([
        ['code' => 'ru', 'name' => 'RU', 'native_name' => 'ru', 'position' => 1, 'is_active' => true, 'is_default' => false],
        ['code' => 'de', 'name' => 'DE', 'native_name' => 'de', 'position' => 1, 'is_active' => true, 'is_default' => false],
    ]);

    expect(Localization::locales()->all()->keys()->all())->toBe(['en', 'de', 'ru']);
});

it('turns a concurrent "first locale" race into a clear exception', function () {
    $this->configureLocalization(['locales_driver' => 'database']);

    // Another process creates the default between our check and our insert.
    Locale::creating(function (Locale $locale) {
        DB::table('locales')->insert(['code' => 'ka', 'name' => 'ka', 'native_name' => 'ka', 'is_active' => true, 'is_default' => true]);
    });

    expect(fn () => resolve(CreateLocale::class)->execute(new CreateLocaleData('en', 'English', 'English')))
        ->toThrow(InvalidLocaleOperationException::class, 'concurrent change');

    expect(DB::table('locales')->count())->toBe(0); // rolled back

    Locale::flushEventListeners();
});
