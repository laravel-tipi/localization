<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tipi\Localization\Actions\ActivateLocale;
use Tipi\Localization\Actions\CreateLocale;
use Tipi\Localization\Data\CreateLocaleData;
use Tipi\Localization\Exceptions\InvalidLocalizedRouteException;
use Tipi\Localization\Tests\Fixtures\EnsureLocalizedParameters;
use Tipi\Localization\Tests\Fixtures\Wine;
use Tipi\Localization\Tests\Fixtures\WineController;

beforeEach(function () {
    Schema::dropIfExists('wines');
    Schema::create('wines', function (Blueprint $table) {
        $table->id();
        $table->string('slug')->unique();
    });

    Wine::query()->create(['slug' => 'saperavi']);
});

function edgeRoutes(bool $cached = false): void
{
    test()->localizedRoutes(function (Router $router) {
        $router->get('/wines/{wine}', [WineController::class, 'show'])->name('wines.show');
        $router->get('/wines/{wine}/reviews/{review}', [WineController::class, 'review'])->name('wines.reviews.show');
        $router->get('/vintages/{region}/{year}', [WineController::class, 'vintage'])->whereNumber('year')->name('vintages');
        $router->get('/archive/{year?}', fn (?string $year = null) => 'archive:'.app()->getLocale().':'.($year ?? 'all'))->name('archive');
        $router->get('/by-id/{wine:id}', fn (Wine $wine) => 'id:'.app()->getLocale().':'.$wine->slug)->name('wines.by-id');

        $router->prefix('shop')->name('shop.')->group(function (Router $router) {
            $router->get('/cart', fn () => 'cart:'.app()->getLocale())->name('cart');
            $router->resource('orders', OrderController::class)->only(['index', 'show']);
        });

        $router->get('/guarded/{wine}', fn (Wine $wine) => 'guarded:'.$wine->slug)
            ->middleware(EnsureLocalizedParameters::class)
            ->name('guarded');
    }, web: true);

    if ($cached) {
        $router = app('router');
        $router->setCompiledRoutes($router->getRoutes()->compile());
    }
}

final class OrderController
{
    public function index(): string
    {
        return 'orders:'.app()->getLocale();
    }

    public function show(string $order): string
    {
        return 'order:'.app()->getLocale().':'.$order;
    }
}

dataset('route modes', ['uncached' => false, 'cached' => true]);

it('resolves implicit model binding on both variants', function (bool $cached) {
    edgeRoutes($cached);

    $this->get('/wines/saperavi')->assertSeeText('wine:en:saperavi');
    $this->get('/ka/wines/saperavi')->assertSeeText('wine:ka:saperavi');
    $this->get('/ka/by-id/1')->assertSeeText('id:ka:saperavi');
    $this->get('/ka/wines/missing')->assertNotFound();
})->with('route modes');

it('passes scalar controller arguments in order, without the locale', function (bool $cached) {
    edgeRoutes($cached);

    $this->get('/ka/vintages/kakheti/2020')->assertSeeText('vintage:ka:kakheti:2020');
    $this->get('/ka/wines/saperavi/reviews/7')->assertSeeText('review:ka:saperavi:7');
    $this->get('/vintages/kakheti/2020')->assertSeeText('vintage:en:kakheti:2020');
})->with('route modes');

it('supports optional parameters', function (bool $cached) {
    edgeRoutes($cached);

    $this->get('/ka/archive')->assertSeeText('archive:ka:all');
    $this->get('/ka/archive/2020')->assertSeeText('archive:ka:2020');
    $this->get('/archive')->assertSeeText('archive:en:all');

    expect(route('archive', ['locale' => 'ka'], false))->toBe('/ka/archive')
        ->and(route('archive', ['year' => 2020, 'locale' => 'ka'], false))->toBe('/ka/archive/2020');
})->with('route modes');

it('supports nested groups and resources', function (bool $cached) {
    edgeRoutes($cached);

    $this->get('/ka/shop/cart')->assertSeeText('cart:ka');
    $this->get('/ka/shop/orders')->assertSeeText('orders:ka');
    $this->get('/ka/shop/orders/42')->assertSeeText('order:ka:42');
    $this->get('/shop/orders/42')->assertSeeText('order:en:42');

    expect(route('shop.orders.show', ['order' => 42, 'locale' => 'ka'], false))->toBe('/ka/shop/orders/42');
})->with('route modes');

it('runs route middleware after the locale is applied and the parameter removed', function (bool $cached) {
    edgeRoutes($cached);

    $this->get('/ka/guarded/saperavi')->assertOk()->assertSeeText('guarded:saperavi');
})->with('route modes');

describe('locale codes that equal static route segments', function () {
    it('fails at registration with the config driver', function () {
        $this->localizedRoutes(fn (Router $router) => $router->get('/de', fn () => 'Germany page')->name('germany'));
    })->throws(InvalidLocalizedRouteException::class, 'Locale [de] collides with the static localized route segment "/de"');

    it('rejects creating or activating a colliding database locale', function () {
        $this->useDatabaseLocales(['en', 'ka'], inactive: ['ka']);
        $this->localizedRoutes(function (Router $router) {
            $router->get('/ka', fn () => 'page')->name('ka-page');
            $router->get('/faq', fn () => 'faq')->name('faq');
        });

        expect(fn () => resolve(ActivateLocale::class)->execute('ka'))
            ->toThrow(InvalidLocalizedRouteException::class, 'Locale [ka] collides')
            ->and(fn () => resolve(CreateLocale::class)->execute(new CreateLocaleData('faq', 'x', 'x')))
            ->toThrow(InvalidLocalizedRouteException::class, 'Locale [faq] collides');
    });

    it('refuses to serve pages instead of silently serving the wrong content when the database is changed directly', function (bool $cached) {
        $this->useDatabaseLocales(['en', 'ka'], inactive: ['ka']);
        $this->localizedRoutes(function (Router $router) {
            $router->get('/ka', fn () => 'page')->name('ka-page');
            $router->get('/wines', fn () => 'wines')->name('wines');
        });

        if ($cached) {
            $router = app('router');
            $router->setCompiledRoutes($router->getRoutes()->compile());
        }

        $this->get('/wines')->assertOk();

        DB::table('locales')->where('code', 'ka')->update(['is_active' => true]);

        // A new request starts with fresh scoped services.
        $this->app->forgetScopedInstances();
        Facade::clearResolvedInstances();

        $this->withoutExceptionHandling();
        $this->get('/wines');
    })->with('route modes')->throws(InvalidLocalizedRouteException::class, 'Locale [ka] collides');

    it('allows locale-shaped static segments that are not locale codes', function () {
        $this->localizedRoutes(fn (Router $router) => $router->get('/faq', fn () => 'faq:'.app()->getLocale())->name('faq'));

        $this->get('/faq')->assertSeeText('faq:en');
        $this->get('/ka/faq')->assertSeeText('faq:ka');
    });
});

it('documents: a regular route named like a localized route after registration wins', function () {
    $this->localizedRoutes(fn (Router $router) => $router->get('/about', fn () => 'about')->name('about'));
    Route::get('/legacy-about', fn () => 'legacy')->name('about');
    app('router')->getRoutes()->refreshNameLookups();

    // Laravel's own lookup succeeds, so the localized resolver is never asked.
    expect(route('about', absolute: false))->toBe('/legacy-about');
});
