<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('creates the locales and context tables', function () {
    expect(Schema::hasColumns('locales', ['code', 'name', 'native_name', 'native_abbreviation', 'country_code', 'text_direction', 'position', 'is_active', 'is_default']))->toBeTrue()
        ->and(Schema::hasColumns('locale_contexts', ['name', 'all_locales', 'default_locale']))->toBeTrue()
        ->and(Schema::hasColumns('locale_context_locales', ['context', 'locale']))->toBeTrue()
        ->and(array_column(Schema::getIndexes('locales'), 'name'))->toContain('locales_one_default');
});

it('stores locale codes up to 16 characters', function () {
    DB::table('locales')->insert(['code' => 'yue-Hant-001', 'name' => 'x', 'native_name' => 'x']);

    expect(DB::table('locales')->value('code'))->toBe('yue-Hant-001');
});

it('allows at most one default locale', function () {
    DB::table('locales')->insert(['code' => 'en', 'name' => 'en', 'native_name' => 'en', 'is_active' => true, 'is_default' => true]);
    DB::table('locales')->insert(['code' => 'ka', 'name' => 'ka', 'native_name' => 'ka', 'is_active' => true, 'is_default' => true]);
})->throws(QueryException::class);

it('allows any number of non-default locales', function () {
    DB::table('locales')->insert([
        ['code' => 'en', 'name' => 'en', 'native_name' => 'en', 'is_active' => true, 'is_default' => true],
        ['code' => 'ka', 'name' => 'ka', 'native_name' => 'ka', 'is_active' => true, 'is_default' => false],
        ['code' => 'ru', 'name' => 'ru', 'native_name' => 'ru', 'is_active' => false, 'is_default' => false],
    ]);

    expect(DB::table('locales')->count())->toBe(3);
});

it('cascades context memberships when a context row is deleted', function () {
    DB::table('locale_contexts')->insert(['name' => 'shop', 'all_locales' => false]);
    DB::table('locale_context_locales')->insert(['context' => 'shop', 'locale' => 'en']);

    DB::table('locale_contexts')->delete();

    expect(DB::table('locale_context_locales')->count())->toBe(0);
});

it('rolls back cleanly', function () {
    foreach (['create_locale_contexts_tables', 'create_locales_table'] as $name) {
        (require __DIR__."/../../../database/migrations/{$name}.php")->down();
    }

    expect(Schema::hasTable('locales'))->toBeFalse()
        ->and(Schema::hasTable('locale_contexts'))->toBeFalse()
        ->and(Schema::hasTable('locale_context_locales'))->toBeFalse();
});
