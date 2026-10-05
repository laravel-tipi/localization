<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->artisan('migrate')->run();
});

it('creates the locales table', function () {
    expect(Schema::hasTable('locales'))->toBeTrue();
});

it('creates the expected locale columns', function () {
    expect(Schema::hasColumns('locales', [
        'code',
        'name',
        'native_name',
        'country_code',
        'text_direction',
        'is_active',
        'is_default',
    ]))->toBeTrue();
});

it('allows only one default locale', function () {
    DB::table('locales')->insert([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'text_direction' => 'ltr',
        'is_active' => true,
        'is_default' => true,
    ]);

    DB::table('locales')->insert([
        'code' => 'ka',
        'name' => 'Georgian',
        'native_name' => 'ქართული',
        'text_direction' => 'ltr',
        'is_active' => true,
        'is_default' => true,
    ]);
})->throws(QueryException::class);

it('allows multiple non-default locales', function () {
    DB::table('locales')->insert([
        [
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'text_direction' => 'ltr',
            'is_active' => true,
            'is_default' => false,
        ],
        [
            'code' => 'ka',
            'name' => 'Georgian',
            'native_name' => 'ქართული',
            'text_direction' => 'ltr',
            'is_active' => true,
            'is_default' => false,
        ],
    ]);

    expect(DB::table('locales')->count())->toBe(2);
});
