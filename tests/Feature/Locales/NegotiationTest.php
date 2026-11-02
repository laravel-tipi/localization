<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Tipi\Localization\Facades\Localization;

function negotiateFor(?string $acceptLanguage, ?string $context = null): string
{
    $request = Request::create('/');

    // Request::create() adds "Accept-Language: en-us,en;q=0.5" by default.
    $acceptLanguage === null
        ? $request->headers->remove('Accept-Language')
        : $request->headers->set('Accept-Language', $acceptLanguage);

    return Localization::negotiate($context, $request)->code;
}

beforeEach(function () {
    // The default is deliberately not the first locale.
    $this->configureLocalization(['default_locale' => 'ka']);
});

it('B1: falls back to the default locale when there is no Accept-Language header', function () {
    expect(negotiateFor(null))->toBe('ka')
        ->and(negotiateFor(''))->toBe('ka');
});

it('B1: falls back to the default locale when nothing matches', function () {
    expect(negotiateFor('fr, es;q=0.8'))->toBe('ka')
        ->and(negotiateFor('*'))->toBe('ka');
});

it('picks the highest-quality supported language', function () {
    expect(negotiateFor('de;q=0.4, ru;q=0.9, en;q=0.7'))->toBe('ru');
});

it('ignores languages with quality zero', function () {
    expect(negotiateFor('ru;q=0, en;q=0.5'))->toBe('en');
});

it('B2: matches regional and script codes without crashing', function () {
    $this->configureLocalization([
        'locales' => [
            ['code' => 'en-US', 'name' => 'English', 'native_name' => 'English'],
            ['code' => 'zh-Hans', 'name' => 'Chinese', 'native_name' => '中文'],
            ['code' => 'ka', 'name' => 'Georgian', 'native_name' => 'ქართული'],
        ],
        'default_locale' => 'ka',
    ]);

    expect(negotiateFor('en-US'))->toBe('en-US')
        ->and(negotiateFor('en_us'))->toBe('en-US')
        ->and(negotiateFor('zh-Hans-CN'))->toBe('zh-Hans')
        ->and(negotiateFor('en-GB, ka;q=0.5'))->toBe('en-US')
        ->and(negotiateFor('en'))->toBe('en-US');
});

it('removes trailing subtags before falling back to the primary language', function () {
    expect(negotiateFor('ru-RU'))->toBe('ru');
});

it('prefers the remembered preference over the browser language', function () {
    $request = Request::create('/', server: ['HTTP_ACCEPT_LANGUAGE' => 'en'], cookies: ['locale_web' => 'ru']);

    expect(Localization::negotiate(request: $request)->code)->toBe('ru');
});

it('ignores a remembered preference the context no longer supports', function () {
    $this->configureLocalization(['contexts' => ['admin' => ['locales' => ['en', 'ka'], 'default' => 'en']]]);

    $request = Request::create('/', server: ['HTTP_ACCEPT_LANGUAGE' => 'ka'], cookies: ['locale_admin' => 'ru']);

    expect(Localization::negotiate('admin', $request)->code)->toBe('ka');
});

it('only negotiates locales the context supports and falls back to the context default', function () {
    $this->configureLocalization(['contexts' => ['admin' => ['locales' => ['en', 'ka'], 'default' => 'en']]]);

    expect(negotiateFor('ru, ka;q=0.5', 'admin'))->toBe('ka')
        ->and(negotiateFor('ru', 'admin'))->toBe('en');
});
