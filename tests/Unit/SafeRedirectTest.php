<?php

declare(strict_types=1);

use Tipi\Localization\Support\SafeRedirect;

it('keeps same-site paths', function (string $path) {
    expect(SafeRedirect::path($path))->toBe($path);
})->with(['/', '/ka/wines', '/wines?page=2&sort=name', '/admin/settings#top']);

it('replaces anything that could leave the site with "/"', function (mixed $target) {
    expect(SafeRedirect::path($target))->toBe('/');
})->with([
    'absolute url' => 'https://evil.test/',
    'protocol relative' => '//evil.test',
    'backslash trick' => '/\\evil.test',
    'scheme' => 'javascript:alert(1)',
    'relative path' => 'wines',
    'newline' => "/ka\r\nLocation: https://evil.test",
    'tab' => "/\t/evil.test",
    'array' => [['/ka']],
    'null' => null,
    'empty' => '',
]);
