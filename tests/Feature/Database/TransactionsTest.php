<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Tipi\Localization\Actions\CreateLocale;
use Tipi\Localization\Actions\CreateLocaleContext;
use Tipi\Localization\Actions\MakeLocaleDefault;
use Tipi\Localization\Actions\UpdateLocaleContext;
use Tipi\Localization\Data\CreateLocaleContextData;
use Tipi\Localization\Data\CreateLocaleData;
use Tipi\Localization\Data\UpdateLocaleContextData;
use Tipi\Localization\Exceptions\InvalidLocaleContextException;
use Tipi\Localization\Exceptions\InvalidLocaleOperationException;
use Tipi\Localization\Facades\Localization;
use Tipi\Localization\Models\LocaleContext;
use Tipi\Localization\Tests\Fixtures\SecondaryLocale;
use Tipi\Localization\Tests\Fixtures\SecondaryLocaleContext;

/**
 * Snapshot of every table the actions write, on both connections.
 *
 * @return array<string, list<array<string, mixed>>>
 */
function storedState(): array
{
    $state = [];

    foreach (['testing' => ['locales', 'locale_contexts', 'locale_context_locales'], 'secondary' => ['languages', 'locale_contexts', 'locale_context_locales']] as $connection => $tables) {
        foreach ($tables as $table) {
            if (DB::connection($connection)->getSchemaBuilder()->hasTable($table)) {
                $state["{$connection}.{$table}"] = DB::connection($connection)->table($table)->get()->map(fn ($row) => (array) $row)->sortBy(fn ($row) => json_encode($row))->values()->all();
            }
        }
    }

    return $state;
}

/**
 * @param  class-string<LocaleContext>  $contextModel
 */
function useSecondaryLocales(string $contextModel, string $contextsDriver = 'database'): void
{
    config()->set('database.connections.secondary', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);

    test()->configureLocalization([
        'locales_driver' => 'database',
        'contexts_driver' => $contextsDriver,
        'models' => ['locale' => SecondaryLocale::class, 'context' => $contextModel],
    ]);

    (require __DIR__.'/../../../database/migrations/create_locales_table.php')->up();

    if ($contextModel === SecondaryLocaleContext::class) {
        (require __DIR__.'/../../../database/migrations/create_locale_contexts_tables.php')->up();
    }

    foreach (['en' => true, 'ka' => false, 'ru' => false] as $code => $default) {
        DB::connection('secondary')->table('languages')->insert([
            'code' => $code, 'name' => $code, 'native_name' => $code, 'is_active' => true, 'is_default' => $default,
        ]);
    }

    test()->configureLocalization([]);
}

describe('locales and contexts on one shared connection', function () {
    beforeEach(fn () => useSecondaryLocales(SecondaryLocaleContext::class));

    it('changes both in one transaction on that connection', function () {
        resolve(CreateLocaleContext::class)->execute(new CreateLocaleContextData('admin', ['en', 'ka']));

        resolve(MakeLocaleDefault::class)->execute('ru');

        expect(DB::connection('secondary')->table('languages')->where('is_default', true)->value('code'))->toBe('ru')
            ->and(DB::connection('secondary')->table('locale_context_locales')->where('context', 'admin')->orderBy('locale')->pluck('locale')->all())->toBe(['en', 'ka', 'ru'])
            ->and(DB::table('locale_contexts')->count())->toBe(0);
    });

    it('rolls back every context already updated when a later one fails', function () {
        resolve(CreateLocaleContext::class)->execute(new CreateLocaleContextData('admin', ['en', 'ka']));
        // Edited outside the actions: its default is not one of its locales.
        DB::connection('secondary')->table('locale_contexts')->insert(['name' => 'shop', 'all_locales' => false, 'default_locale' => 'ka']);
        DB::connection('secondary')->table('locale_context_locales')->insert(['context' => 'shop', 'locale' => 'en']);
        $before = storedState();

        expect(fn () => resolve(MakeLocaleDefault::class)->execute('ru'))
            ->toThrow(InvalidLocaleContextException::class, '[shop]');

        expect(storedState())->toBe($before)
            ->and(Localization::locales()->defaultCode())->toBe('en');
    });
});

describe('locales and contexts on different connections', function () {
    beforeEach(fn () => useSecondaryLocales(LocaleContext::class));

    it('rejects a change before writing anything', function (Closure $operation) {
        $before = storedState();

        expect($operation)->toThrow(InvalidLocaleOperationException::class, 'must be stored on the same database connection');

        expect(storedState())->toBe($before);
    })->with([
        'make default' => fn () => resolve(MakeLocaleDefault::class)->execute('ka'),
        'create locale' => fn () => resolve(CreateLocale::class)->execute(new CreateLocaleData('de', 'German', 'Deutsch')),
        'create context' => fn () => resolve(CreateLocaleContext::class)->execute(new CreateLocaleContextData('admin', ['en'])),
        'update context' => fn () => resolve(UpdateLocaleContext::class)->execute('web', new UpdateLocaleContextData(['en', 'ka'])),
    ]);

    it('names both connections in the error', function () {
        resolve(MakeLocaleDefault::class)->execute('ka');
    })->throws(InvalidLocaleOperationException::class, 'the locale model uses [secondary] and the context model uses [testing]');

    it('keeps reading from both connections', function () {
        expect(Localization::locales()->activeCodes())->toBe(['en', 'ka', 'ru'])
            ->and(Localization::context()->codes())->toBe(['en', 'ka', 'ru']);
    });
});

it('only needs the locale connection when contexts come from config', function () {
    useSecondaryLocales(LocaleContext::class, contextsDriver: 'config');

    resolve(MakeLocaleDefault::class)->execute('ka');

    expect(DB::connection('secondary')->table('languages')->where('is_default', true)->value('code'))->toBe('ka');
});
