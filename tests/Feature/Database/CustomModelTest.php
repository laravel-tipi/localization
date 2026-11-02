<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tipi\Localization\Actions\CreateLocale;
use Tipi\Localization\Data\CreateLocaleData;
use Tipi\Localization\Facades\Localization;
use Tipi\Localization\Rules\LocaleRules;
use Tipi\Localization\Tests\Fixtures\SecondaryLocale;

beforeEach(function () {
    config()->set('database.connections.secondary', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);

    $this->configureLocalization([
        'locales_driver' => 'database',
        'models' => ['locale' => SecondaryLocale::class],
    ]);

    (require __DIR__.'/../../../database/migrations/create_locales_table.php')->up();
});

it('B13: uses the custom model table and connection everywhere', function () {
    resolve(CreateLocale::class)->execute(new CreateLocaleData('en', 'English', 'English'));
    resolve(CreateLocale::class)->execute(new CreateLocaleData('ka', 'Georgian', 'ქართული', active: true));

    expect(DB::connection('secondary')->table('languages')->count())->toBe(2)
        ->and(DB::table('locales')->count())->toBe(0)
        ->and(Localization::locales()->activeCodes())->toBe(['en', 'ka']);

    $validator = Validator::make(
        ['code' => 'ka', 'name' => 'x', 'native_name' => 'x'],
        LocaleRules::create(),
    );

    expect($validator->errors()->has('code'))->toBeTrue();
});
