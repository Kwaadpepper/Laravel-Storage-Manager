<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Config;

use Closure;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Vite;

final readonly class FrontendConfig
{
    public function __construct(
        public bool $devServerEnabled,
        public string $devServerUrl,
        public Closure | string | null $cspNonceConfig
    ) {
    }

    public function getCspNonce(): ?string
    {
        if ($this->cspNonceConfig !== null) {
            return $this->resolveConfiguredCspNonce($this->cspNonceConfig);
        }

        return $this->autoDetectCspNonce();
    }

    private function resolveConfiguredCspNonce(Closure | string $configured): ?string
    {
        if ($configured instanceof Closure) {
            /** @var mixed $result */
            $result = $configured();

            return is_string($result) && $result !== '' ? $result : null;
        }

        $request = Request::instance();
        if ($request !== null) {
            /** @var mixed $attr */
            $attr = $request->attributes->get($configured);
            if (is_string($attr) && $attr !== '') {
                return $attr;
            }
        }

        return $configured;
    }

    private function autoDetectCspNonce(): ?string
    {
        $request = Request::instance();
        if ($request !== null) {
            /** @var mixed $attrNonce */
            $attrNonce = $request->attributes->get('csp_nonce');
            if (is_string($attrNonce) && $attrNonce !== '') {
                return $attrNonce;
            }

            /** @var mixed $attrCspNonce */
            $attrCspNonce = $request->attributes->get('cspNonce');
            if (is_string($attrCspNonce) && $attrCspNonce !== '') {
                return $attrCspNonce;
            }
        }

        /** @var string $cspNonceFn */
        $cspNonceFn = 'csp_nonce';
        if (function_exists($cspNonceFn)) {
            /** @var mixed $fnNonce */
            $fnNonce = $cspNonceFn();
            if (is_string($fnNonce) && $fnNonce !== '') {
                return $fnNonce;
            }
        }

        try {
            $viteNonce = Vite::cspNonce();
            if (is_string($viteNonce) && $viteNonce !== '') {
                return $viteNonce;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
