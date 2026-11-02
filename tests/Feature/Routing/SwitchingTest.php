<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Tipi\Localization\Exceptions\InvalidLocaleConfigurationException;
use Tipi\Localization\Exceptions\UnsupportedLocaleException;
use Tipi\Localization\Facades\Localization;
use Tipi\Localization\Http\Middleware\UseLocaleContext;

beforeEach(function () {
    $this->configureLocalization([
        'negotiate_root_locale' => true,
        'contexts' => [
            'web' => ['locales' => '*'],
            'admin' => ['locales' => ['en', 'ka'], 'default' => 'en'],
        ],
    ]);

    $this->localizedRoutes(function (Router $router) {
        $router->get('/', fn () => 'home:'.app()->getLocale())->name('home');
        $router->get('/wines/{wine}', fn () => 'wine:'.app()->getLocale())->name('wines.show');
        $router->get('/links', fn () => json_encode([
            'switch' => Localization::switchUrl('ka'),
            'switcher' => Localization::switcher()->map(fn ($link) => [$link->locale->code, $link->url, $link->current]),
        ]))->name('links');
        $router->get('/select/{code}', function (string $code) {
            Localization::select($code);

            return 'selected:'.app()->getLocale();
        })->name('select');
    }, web: true);

    Route::middleware(['web', UseLocaleContext::with('admin')])->group(function () {
        Route::get('/admin', fn () => 'admin:'.app()->getLocale().':'.Localization::context()->name);
        Route::get('/admin/links', fn () => json_encode([
            'switch' => Localization::switchUrl('ka'),
            'switcher' => Localization::switcher()->map(fn ($link) => $link->locale->code),
        ]));
        Route::get('/admin/select/{code}', function (string $code) {
            Localization::select($code);

            return 'selected:'.app()->getLocale();
        });
    });
});

it('records the choice in an encrypted per-context cookie and redirects back', function () {
    $response = $this->get('/locale/ka?redirect=/ka/wines/10')
        ->assertRedirect('/ka/wines/10')
        ->assertCookie('locale_web', 'ka');

    $raw = collect($response->headers->getCookies())->firstWhere(fn ($cookie) => $cookie->getName() === 'locale_web');

    expect($raw->getValue())->not->toBe('ka'); // encrypted by EncryptCookies
});

it('cannot be used as an open redirect', function (string $redirect) {
    $this->get('/locale/ka?redirect='.urlencode($redirect))->assertRedirect('/');
})->with(['https://evil.test', '//evil.test', '/\\evil.test', 'javascript:alert(1)']);

it('returns 404 for unknown locales, unsupported locales and unknown contexts', function () {
    $this->get('/locale/fr')->assertNotFound();
    $this->get('/locale/ru?context=admin')->assertNotFound();
    $this->get('/locale/ka?context=missing')->assertNotFound();
});

it('B3: lets visitors switch back to the default locale on a negotiated root', function () {
    $this->withCookie('locale_web', 'ka')->get('/')->assertRedirect('/ka');

    $this->withCookie('locale_web', 'ka')
        ->get('/locale/en?redirect=/')
        ->assertRedirect('/')
        ->assertCookie('locale_web', 'en');

    $this->withCookie('locale_web', 'en')
        ->withHeader('Accept-Language', 'ka')
        ->get('/')
        ->assertOk()
        ->assertSeeText('home:en');
});

it('B11: visiting localized pages never writes the preference', function () {
    $this->get('/ka/wines/10')->assertOk()->assertCookieMissing('locale_web');
    $this->withCookie('locale_web', 'ru')->get('/ka/wines/10')->assertCookieMissing('locale_web');
});

it('B11: negotiated root responses vary on Accept-Language and Cookie', function () {
    $ok = $this->withHeader('Accept-Language', 'en')->get('/');
    $redirect = $this->withHeader('Accept-Language', 'ka')->get('/');

    expect($ok->baseResponse->getVary())->toContain('Accept-Language', 'Cookie')
        ->and($redirect->baseResponse->getVary())->toContain('Accept-Language', 'Cookie');
});

it('only negotiates the configured root route', function () {
    $this->withHeader('Accept-Language', 'ka')->get('/wines/10')->assertSeeText('wine:en');
});

it('B7: select() persists the preference cookie and applies the locale immediately', function () {
    $this->get('/select/ka')->assertSeeText('selected:ka')->assertCookie('locale_web', 'ka');
});

it('does not rewrite an unchanged preference cookie', function () {
    $this->withCookie('locale_web', 'ka')->get('/select/ka')->assertCookieMissing('locale_web');
});

it('rejects selecting a locale the context does not support', function () {
    $this->withoutExceptionHandling()->get('/admin/select/ru');
})->throws(UnsupportedLocaleException::class);

it('B22: keeps admin and public preferences independent', function () {
    $this->get('/admin/select/ka')->assertCookie('locale_admin', 'ka')->assertCookieMissing('locale_web');

    $this->withCookie('locale_admin', 'ka')->withCookie('locale_web', 'ru')
        ->get('/admin')->assertSeeText('admin:ka:admin');

    $this->withCookie('locale_admin', 'ka')->withCookie('locale_web', 'ru')
        ->get('/')->assertRedirect('/ru');
});

it('applies the context default, browser language and preference in non-localized areas', function () {
    $this->get('/admin')->assertSeeText('admin:en:admin');
    $this->withHeader('Accept-Language', 'ka')->get('/admin')->assertSeeText('admin:ka:admin');
    $this->withHeader('Accept-Language', 'ru')->get('/admin')->assertSeeText('admin:en:admin');
    $this->withCookie('locale_admin', 'ka')->get('/admin')->assertSeeText('admin:ka:admin');
});

it('builds switch URLs that return to the current page in the chosen locale', function () {
    $links = $this->get('/ka/links')->json();

    expect(urldecode($links['switch']))->toBe('http://localhost/locale/ka?redirect=/ka/links')
        ->and($links['switcher'][0])->toBe(['en', 'http://localhost/locale/en?redirect=%2Flinks', false])
        ->and($links['switcher'][1][2])->toBeTrue()
        ->and(count($links['switcher']))->toBe(4);
});

it('builds switch URLs for other contexts that return to the current path', function () {
    $links = $this->get('/admin/links?tab=2')->json();

    expect(urldecode($links['switch']))->toBe('http://localhost/locale/ka?redirect=/admin/links?tab=2&context=admin')
        ->and($links['switcher'])->toBe(['en', 'ka']);

    $this->get('/locale/ka?context=admin&redirect=/admin')->assertRedirect('/admin')->assertCookie('locale_admin', 'ka');
});

it('forgets a preference', function () {
    app()->instance('request', request()->duplicate(cookies: ['locale_web' => 'ka']));

    expect(Localization::preference()?->code)->toBe('ka');

    Localization::forgetPreference();

    $queued = app('cookie')->getQueuedCookies();
    expect($queued[0]->getName())->toBe('locale_web')->and($queued[0]->getExpiresTime())->toBeLessThan(time());
});

it('explains that switch URLs need the switch route', function () {
    $this->configureLocalization(['switch_route' => ['enabled' => false]]);

    Localization::switchUrl('ka');
})->throws(InvalidLocaleConfigurationException::class, 'switch route is disabled');
