<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Kwaadpepper\LaravelStorageManager\Config\FrontendConfig;

describe('FrontendConfig', function (): void {
    it('resolves csp nonce when configured as closure', function (): void {
        // GIVEN
        $config = new FrontendConfig(
            devServerEnabled: false,
            devServerUrl: 'http://localhost:5173',
            cspNonceConfig: fn (): string => 'closure-nonce-123',
        );

        // WHEN
        $nonce = $config->getCspNonce();

        // THEN
        expect($nonce)->toBe('closure-nonce-123');
    });

    it('resolves csp nonce from request attribute or direct string', function (): void {
        // GIVEN
        $request = Request::create('/');
        $request->attributes->set('my_custom_nonce_key', 'attr-nonce-456');
        app()->instance('request', $request);

        $configAttr = new FrontendConfig(
            devServerEnabled: false,
            devServerUrl: 'http://localhost:5173',
            cspNonceConfig: 'my_custom_nonce_key',
        );
        $configDirect = new FrontendConfig(
            devServerEnabled: false,
            devServerUrl: 'http://localhost:5173',
            cspNonceConfig: 'static-nonce-789',
        );

        // WHEN
        $nonceFromAttr = $configAttr->getCspNonce();
        $nonceDirect   = $configDirect->getCspNonce();

        // THEN
        expect($nonceFromAttr)->toBe('attr-nonce-456');
        expect($nonceDirect)->toBe('static-nonce-789');
    });

    it('auto-detects csp nonce from request attributes when config is null', function (): void {
        // GIVEN
        $request = Request::create('/');
        $request->attributes->set('csp_nonce', 'auto-detected-nonce');
        app()->instance('request', $request);

        $config = new FrontendConfig(
            devServerEnabled: false,
            devServerUrl: 'http://localhost:5173',
            cspNonceConfig: null,
        );

        // WHEN
        $nonce = $config->getCspNonce();

        // THEN
        expect($nonce)->toBe('auto-detected-nonce');
    });
})->covers(FrontendConfig::class);
