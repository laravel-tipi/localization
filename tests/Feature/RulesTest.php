<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Tipi\Localization\Rules\LocaleContextRules;
use Tipi\Localization\Rules\LocaleRules;

it('validates locale forms', function (array $data, array $errors) {
    $this->useDatabaseLocales(['en']);

    $validator = Validator::make($data, LocaleRules::create(), [], LocaleRules::attributes());

    expect(array_keys($validator->errors()->toArray()))->toEqualCanonicalizing($errors);
})->with([
    'valid' => [['code' => 'zh-Hans-CN', 'name' => 'Chinese', 'native_name' => '中文', 'text_direction' => 'ltr'], []],
    'existing' => [['code' => 'en', 'name' => 'x', 'native_name' => 'x'], ['code']],
    'format' => [['code' => 'en_US', 'name' => 'x', 'native_name' => 'x', 'country_code' => 'us', 'native_abbreviation' => 'ENGL'], ['code', 'native_abbreviation', 'country_code']],
]);

it('validates context forms', function (array $data, array $errors) {
    $this->configureLocalization(['contexts' => ['admin' => ['locales' => ['en']]]]);

    $validator = Validator::make($data, LocaleContextRules::create());

    expect(array_keys($validator->errors()->toArray()))->toEqualCanonicalizing($errors);
})->with([
    'valid' => [['name' => 'shop', 'locales' => ['en', 'ka'], 'default' => 'ka'], []],
    'declared' => [['name' => 'admin'], ['name']],
    'format' => [['name' => 'Shop', 'locales' => ['en_US'], 'default' => 'EN'], ['name', 'locales.0', 'default']],
]);
