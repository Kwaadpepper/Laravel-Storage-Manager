<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Http\Middleware\StorageManagerMiddleware;
use Tests\TestCase;

describe('StorageManagerMiddleware', function (): void {
    beforeEach(function (): void {
        assert($this instanceof TestCase);
        Config::set('storage-manager.enabled', true);
        Config::set('storage-manager.auth.enabled', false);
        Config::set('storage-manager.route.prefix', 'sm');
        Config::set('storage-manager.frontend.csp_nonce', null);
        App::forgetInstance(StorageManagerConfig::class);
    });

    it('automatically generates nonce per request and injects it in html and csp header', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $url = '/sm';

        // WHEN
        $response  = $this->get($url);
        $cspHeader = (string) $response->headers->get('Content-Security-Policy');
        preg_match("/script-src 'self' 'nonce-([^']+)'/", $cspHeader, $matches);
        $nonce = $matches[1] ?? '';

        // THEN
        $response->assertStatus(200);
        expect($nonce)->not->toBeEmpty();
        expect($cspHeader)->toContain("script-src 'self' 'nonce-{$nonce}'");
        expect($response->getContent())->toContain('nonce="' . $nonce . '"');
    });

    it('reuses configured csp nonce in html and csp header', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        Config::set('storage-manager.frontend.csp_nonce', 'my-custom-configured-nonce');
        App::forgetInstance(StorageManagerConfig::class);

        // WHEN
        $response  = $this->get('/sm');
        $cspHeader = (string) $response->headers->get('Content-Security-Policy');

        // THEN
        $response->assertStatus(200);
        expect($cspHeader)->toContain("script-src 'self' 'nonce-my-custom-configured-nonce'");
        expect($response->getContent())->toContain('nonce="my-custom-configured-nonce"');
    });

    it('sends security headers on storage manager response', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $url = '/sm';

        // WHEN
        $response = $this->get($url);

        // THEN
        $response->assertStatus(200);
        expect($response->headers->get('Permissions-Policy'))
            ->toBe('camera=(), microphone=(), geolocation=(), payment=(), usb=(), fullscreen=(self)');
        expect($response->headers->get('X-Content-Type-Options'))
            ->toBe('nosniff');
        expect($response->headers->get('Referrer-Policy'))
            ->toBe('strict-origin-when-cross-origin');
    });

    it('defaults to English when no preferred language is sent', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $url = '/sm';

        // WHEN
        $response = $this->get($url);

        // THEN
        $response->assertStatus(200);
        expect(App::getLocale())->toBe('en');
    });

    it('uses preferred language from Accept-Language header when french is requested', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $url = '/sm';

        // WHEN
        $response = $this->withHeaders(['Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8'])->get($url);

        // THEN
        $response->assertStatus(200);
        expect(App::getLocale())->toBe('fr');
    });

    it('switches to french when lang parameter is explicitly requested in query', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $url = '/sm?lang=fr';

        // WHEN
        $response = $this->withHeaders(['Accept-Language' => 'en-US,en;q=0.9'])->get($url);

        // THEN
        $response->assertStatus(200);
        expect(App::getLocale())->toBe('fr');
    });

    it('switches to english when lang parameter is explicitly requested in query', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $url = '/sm?lang=en';

        // WHEN
        $response = $this->withHeaders(['Accept-Language' => 'fr-FR,fr;q=0.9'])->get($url);

        // THEN
        $response->assertStatus(200);
        expect(App::getLocale())->toBe('en');
    });

    it('falls back to english when preferred language is not supported', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $url = '/sm';

        // WHEN
        $response = $this->withHeaders(['Accept-Language' => 'es-ES,es;q=0.9,de;q=0.8'])->get($url);

        // THEN
        $response->assertStatus(200);
        expect(App::getLocale())->toBe('en');
    });
})->covers(StorageManagerMiddleware::class);
