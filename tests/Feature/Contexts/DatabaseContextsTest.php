<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Tipi\Localization\Actions\CreateLocaleContext;
use Tipi\Localization\Actions\DeleteLocaleContext;
use Tipi\Localization\Actions\UpdateLocaleContext;
use Tipi\Localization\Data\CreateLocaleContextData;
use Tipi\Localization\Data\UpdateLocaleContextData;
use Tipi\Localization\Exceptions\InvalidLocaleContextException;
use Tipi\Localization\Exceptions\InvalidLocaleOperationException;
use Tipi\Localization\Exceptions\LocaleContextNotFoundException;
use Tipi\Localization\Facades\Localization;
use Tipi\Localization\Models\LocaleContext;

beforeEach(function () {
    $this->useDatabaseLocales(['en', 'ka', 'ru', 'de'], inactive: ['de']);
    $this->configureLocalization([
        'contexts_driver' => 'database',
        'contexts' => [
            'web' => ['locales' => '*'],
            'admin' => ['locales' => ['en', 'ka'], 'default' => 'en'],
        ],
    ]);
});

it('uses declared contexts until they are stored, without writing on read', function () {
    expect(Localization::contexts()->names())->toBe(['web', 'admin'])
        ->and(Localization::context('admin')->codes())->toBe(['en', 'ka'])
        ->and(LocaleContext::query()->count())->toBe(0);
});

it('creates contexts that are immediately usable', function () {
    $model = resolve(CreateLocaleContext::class)->execute(
        new CreateLocaleContextData('shop', ['en', 'ru'], 'ru'),
    );

    expect($model)->toBeInstanceOf(LocaleContext::class)
        ->and($model->localeCodes())->toBe(['en', 'ru'])
        ->and(Localization::context('shop')->codes())->toBe(['en', 'ru'])
        ->and(Localization::context('shop')->defaultCode())->toBe('ru')
        ->and(Localization::contexts()->definition('shop')->required)->toBeFalse();
});

it('creates "*" contexts', function () {
    resolve(CreateLocaleContext::class)->execute(new CreateLocaleContextData('kiosk'));

    expect(Localization::context('kiosk')->codes())->toBe(['en', 'ka', 'ru'])
        ->and(LocaleContext::query()->find('kiosk')->localeCodes())->toBeNull();
});

it('rejects creating an existing or declared context', function (string $name) {
    resolve(CreateLocaleContext::class)->execute(new CreateLocaleContextData('shop', ['en']));

    resolve(CreateLocaleContext::class)->execute(new CreateLocaleContextData($name, ['en']));
})->with(['shop', 'admin', 'web'])->throws(InvalidLocaleOperationException::class, 'already exists');

it('validates every invariant before storing anything', function (UpdateLocaleContextData|CreateLocaleContextData $data, string $message) {
    expect(fn () => $data instanceof CreateLocaleContextData
        ? resolve(CreateLocaleContext::class)->execute($data)
        : resolve(UpdateLocaleContext::class)->execute('admin', $data)
    )->toThrow(InvalidLocaleContextException::class, $message);

    expect(LocaleContext::query()->count())->toBe(0)
        ->and(DB::table('locale_context_locales')->count())->toBe(0);
})->with([
    'missing global default' => [new CreateLocaleContextData('shop', ['ka']), 'must contain the global default locale [en]'],
    'default outside' => [new UpdateLocaleContextData(['en', 'ka'], 'ru'), 'is not one of its locales'],
    'unknown locale' => [new UpdateLocaleContextData(['en', 'fr']), 'does not exist in the locale registry'],
    'inactive default' => [new UpdateLocaleContextData(['en', 'de'], 'de'), 'is not active'],
]);

it('stores a declared context on first update and keeps it required', function () {
    resolve(UpdateLocaleContext::class)->execute('admin', new UpdateLocaleContextData(['en', 'ka', 'ru'], 'ka'));

    $definition = Localization::contexts()->definition('admin');

    expect($definition->persisted)->toBeTrue()
        ->and($definition->required)->toBeTrue()
        ->and(Localization::context('admin')->codes())->toBe(['en', 'ka', 'ru'])
        ->and(Localization::context('admin')->defaultCode())->toBe('ka');
});

it('replaces memberships on update', function () {
    resolve(CreateLocaleContext::class)->execute(new CreateLocaleContextData('shop', ['en', 'ru']));
    resolve(UpdateLocaleContext::class)->execute('shop', new UpdateLocaleContextData(['en', 'ka']));

    expect(DB::table('locale_context_locales')->where('context', 'shop')->orderBy('locale')->pluck('locale')->all())
        ->toBe(['en', 'ka']);
});

it('throws when updating an unknown context', function () {
    resolve(UpdateLocaleContext::class)->execute('missing', new UpdateLocaleContextData);
})->throws(LocaleContextNotFoundException::class);

it('deletes stored contexts with their memberships', function () {
    resolve(CreateLocaleContext::class)->execute(new CreateLocaleContextData('shop', ['en', 'ru']));
    resolve(DeleteLocaleContext::class)->execute('shop');

    expect(Localization::contexts()->has('shop'))->toBeFalse()
        ->and(DB::table('locale_context_locales')->count())->toBe(0);
});

it('cannot delete required contexts, even after they were stored', function (string $name) {
    resolve(UpdateLocaleContext::class)->execute('admin', new UpdateLocaleContextData(['en']));

    resolve(DeleteLocaleContext::class)->execute($name);
})->with(['web', 'admin'])->throws(InvalidLocaleOperationException::class, 'cannot be deleted');

it('supports config-defined locales with database-managed contexts', function () {
    DB::table('locales')->delete();
    $this->configureLocalization(['locales_driver' => 'config']);

    resolve(CreateLocaleContext::class)->execute(new CreateLocaleContextData('shop', ['en', 'de'], 'de'));

    expect(Localization::context('shop')->codes())->toBe(['en', 'de'])
        ->and(Localization::context('shop')->defaultCode())->toBe('de');
});

it('refuses to manage contexts with the config contexts driver', function () {
    $this->configureLocalization(['contexts_driver' => 'config']);

    resolve(CreateLocaleContext::class)->execute(new CreateLocaleContextData('shop'));
})->throws(InvalidLocaleOperationException::class, '"database" contexts driver');
