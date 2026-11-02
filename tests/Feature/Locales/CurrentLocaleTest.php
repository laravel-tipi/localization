<?php

declare(strict_types=1);

use Illuminate\Support\Facades\App;
use Illuminate\Support\Traits\Localizable;
use Tipi\Localization\Facades\Localization;

it('B8: follows App::setLocale() after the current locale was read', function () {
    $this->localizedRoutes(fn ($router) => $router->get('/', fn () => 'home')->name('home'));
    App::setLocale('en');
    expect(Localization::currentCode())->toBe('en');

    App::setLocale('ka');
    expect(Localization::currentCode())->toBe('ka')
        ->and(route('home', absolute: false))->toBe('/ka');
});

it('B8: follows withLocale(), as used by mailables and notifications', function () {
    $this->localizedRoutes(fn ($router) => $router->get('/', fn () => 'home')->name('home'));
    App::setLocale('en');
    Localization::current();

    $mailable = new class
    {
        use Localizable;

        public function render(): array
        {
            return $this->withLocale('ka', fn () => [Localization::currentCode(), route('home', absolute: false)]);
        }
    };

    $inside = $mailable->render();

    expect($inside)->toBe(['ka', '/ka'])
        ->and(Localization::currentCode())->toBe('en');
});

it('B9: falls back to the context default when the application locale is not an active locale', function () {
    $this->useDatabaseLocales(['ka', 'ru'], default: 'ka', inactive: ['ru']);

    App::setLocale('en');
    expect(Localization::currentCode())->toBe('ka');

    App::setLocale('ru');
    expect(Localization::currentCode())->toBe('ka');
});

it('falls back to the current context default rather than the global default', function () {
    $this->configureLocalization(['contexts' => ['admin' => ['locales' => ['en', 'ka'], 'default' => 'ka']]]);
    Localization::contexts()->setCurrent('admin');

    App::setLocale('fr');

    expect(Localization::currentCode())->toBe('ka');
});

it('reports any active application locale, even outside the current context', function () {
    $this->configureLocalization(['contexts' => ['admin' => ['locales' => ['en']]]]);
    Localization::contexts()->setCurrent('admin');

    App::setLocale('de');

    expect(Localization::currentCode())->toBe('de');
});
