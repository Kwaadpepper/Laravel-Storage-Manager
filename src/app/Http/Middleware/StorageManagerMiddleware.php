<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Service\ApiService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class StorageManagerMiddleware
{
    public function __construct(
        private readonly ApiService $apiService,
        private readonly StorageManagerConfig $config
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request):(Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->apiService->isAllowedToRequest($request)) {
            throw new HttpException(403, 'Forbidden');
        }

        $nonce = $this->config->frontend->getCspNonce();
        if ($nonce === null) {
            $nonce = base64_encode(random_bytes(16));
            $request->attributes->set('csp_nonce', $nonce);
        }

        $response = $next($request);

        $devOrigin = '';
        $devWs     = '';

        if ($this->config->frontend->devServerEnabled) {
            $devUrl    = rtrim($this->config->frontend->devServerUrl, '/');
            $devOrigin = ' ' . $devUrl;
            $wsScheme  = str_starts_with($devUrl, 'https://') ? 'wss://' : 'ws://';
            $wsHost    = preg_replace('#^https?://#', '', $devUrl) ?? '';
            $devWs     = ' ' . $wsScheme . $wsHost;
        }

        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'{$devOrigin}",
            "style-src 'self' 'unsafe-inline'{$devOrigin}",
            "img-src 'self' data: blob: https: http:",
            "media-src 'self' blob: https: http:",
            "font-src 'self' data:{$devOrigin}",
            "connect-src 'self' https: http:{$devOrigin}{$devWs}",
            "worker-src 'self' blob:",
            "frame-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "frame-ancestors 'self'",
        ];

        $csp = implode('; ', $cspDirectives);

        if (! $response->headers->has('Permissions-Policy')) {
            $response->headers->set(
                'Permissions-Policy',
                'camera=(), microphone=(), geolocation=(), payment=(), usb=(), fullscreen=(self)'
            );
        }

        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $csp);
        }

        if (! $response->headers->has('X-Content-Type-Options')) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        if (! $response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        return $response;
    }
}
