<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Tipi\Localization\Actions\CreateLocaleContext;
use Tipi\Localization\Actions\DeactivateLocale;
use Tipi\Localization\Actions\MakeLocaleDefault;
use Tipi\Localization\Actions\UpdateLocaleContext;
use Tipi\Localization\Data\CreateLocaleContextData;
use Tipi\Localization\Data\UpdateLocaleContextData;
use Tipi\Localization\Exceptions\InvalidLocaleContextException;
use Tipi\Localization\Exceptions\InvalidLocaleOperationException;
use Tipi\Localization\Facades\Localization;
use Tipi\Localization\LocaleContext;

function assertContextInvariants(): void
{
    $global = Localization::locales()->defaultCode();

    Localization::contexts()->all()->each(function (LocaleContext $context) use ($global) {
        expect($context->codes())->toContain($global)
            ->and($context->codes())->toContain($context->defaultCode())
            ->and(array_diff($context->codes(), Localization::locales()->activeCodes()))->toBe([]);
    });
}

it('resolves the same definition identically from config and from the database', function (string|array $locales, ?string $default) {
    $this->useDatabaseLocales(['en', 'ka', 'ru', 'de'], inactive: ['de']);
    $this->configureLocalization(['contexts' => ['admin' => ['locales' => $locales, 'default' => $default]]]);

    $fromConfig = Localization::context('admin');

    $this->configureLocalization(['contexts_driver' => 'database', 'contexts' => []]);
    resolve(CreateLocaleContext::class)->execute(new CreateLocaleContextData('admin', $locales === '*' ? null : $locales, $default));

    $fromDatabase = Localization::context('admin');

    expect($fromDatabase->codes())->toBe($fromConfig->codes())
        ->and($fromDatabase->defaultCode())->toBe($fromConfig->defaultCode())
        ->and($fromDatabase->includesAllLocales())->toBe($fromConfig->includesAllLocales());
})->with([
    'all locales' => ['*', null],
    'subset with own default' => [['en', 'ka'], 'ka'],
    'subset following global default' => [['ru', 'en'], null],
    'inactive member filtered' => [['en', 'de'], null],
]);

it('rejects the same invalid definition from config and from the database', function () {
    $this->useDatabaseLocales(['en', 'ka']);
    $this->configureLocalization(['contexts' => ['admin' => ['locales' => ['ka']]]]);

    expect(fn () => Localization::context('admin'))->toThrow(InvalidLocaleContextException::class, 'global default');

    $this->configureLocalization(['contexts_driver' => 'database', 'contexts' => []]);

    expect(fn () => resolve(CreateLocaleContext::class)->execute(new CreateLocaleContextData('admin', ['ka'])))
        ->toThrow(InvalidLocaleContextException::class, 'global default');
});

it('keeps every invariant through a sequence of global default changes', function () {
    $this->useDatabaseLocales(['en', 'ka', 'ru', 'de']);
    $this->configureLocalization([
        'contexts_driver' => 'database',
        'contexts' => [
            'web' => ['locales' => '*'],
            'admin' => ['locales' => ['en', 'ka'], 'default' => 'ka'],
        ],
    ]);
    resolve(CreateLocaleContext::class)->execute(new CreateLocaleContextData('shop', ['en', 'de'], 'de'));

    assertContextInvariants();

    foreach (['ru', 'de', 'ka', 'en'] as $code) {
        resolve(MakeLocaleDefault::class)->execute($code);

        expect(Localization::locales()->defaultCode())->toBe($code);
        assertContextInvariants();
    }

    // Context defaults are preserved; global defaults were added, never removed.
    expect(Localization::context('admin')->defaultCode())->toBe('ka')
        ->and(Localization::context('admin')->codes())->toBe(['en', 'ka', 'ru', 'de'])
        ->and(Localization::context('shop')->defaultCode())->toBe('de')
        ->and(DB::table('locales')->where('is_default', true)->count())->toBe(1);
});

it('protects a context default from deactivation until the context changes it', function () {
    $this->useDatabaseLocales(['en', 'ka']);
    $this->configureLocalization(['contexts_driver' => 'database', 'contexts' => ['admin' => ['locales' => ['en', 'ka'], 'default' => 'ka']]]);

    expect(fn () => resolve(DeactivateLocale::class)->execute('ka'))->toThrow(InvalidLocaleOperationException::class);

    resolve(UpdateLocaleContext::class)->execute('admin', new UpdateLocaleContextData(['en', 'ka'], 'en'));
    resolve(DeactivateLocale::class)->execute('ka');

    expect(Localization::context('admin')->codes())->toBe(['en']);
    assertContextInvariants();
});

it('stores nothing when a context update is rejected', function () {
    $this->useDatabaseLocales(['en', 'ka']);
    $this->configureLocalization(['contexts_driver' => 'database', 'contexts' => ['admin' => ['locales' => ['en', 'ka']]]]);
    resolve(UpdateLocaleContext::class)->execute('admin', new UpdateLocaleContextData(['en', 'ka'], 'ka'));

    expect(fn () => resolve(UpdateLocaleContext::class)->execute('admin', new UpdateLocaleContextData(['ka'])))
        ->toThrow(InvalidLocaleContextException::class);

    expect(DB::table('locale_context_locales')->where('context', 'admin')->orderBy('locale')->pluck('locale')->all())->toBe(['en', 'ka'])
        ->and(DB::table('locale_contexts')->where('name', 'admin')->value('default_locale'))->toBe('ka');
});
