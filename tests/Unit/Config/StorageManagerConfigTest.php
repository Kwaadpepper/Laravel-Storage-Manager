<?php

declare(strict_types=1);

use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Vite;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;

describe('StorageManagerConfig', function (): void {
    it('retrieves and normalizes allowed extensions list', function (): void {
        // GIVEN
        $config = StorageManagerConfig::fromArray(
            [
                'upload' => [
                    'allowed_extensions' => ['JPG', ' .png ', 'pdf', '', 'JPG'],
                ],
            ],
            []
        );

        // WHEN
        $allowedExtensions = $config->upload->allowedExtensions;

        // THEN
        expect($allowedExtensions)->toBe(['jpg', 'png', 'pdf']);
    });

    it('verifies if an extension is allowed', function (): void {
        // GIVEN
        $config = StorageManagerConfig::fromArray(
            [
                'upload' => [
                    'allowed_extensions' => ['jpg', 'png', 'pdf'],
                ],
            ],
            []
        );

        // WHEN
        $isJpgAllowed    = $config->upload->isExtensionAllowed('jpg');
        $isDotJpgAllowed = $config->upload->isExtensionAllowed('.JPG');
        $isPngAllowed    = $config->upload->isExtensionAllowed('png');
        $isExeAllowed    = $config->upload->isExtensionAllowed('exe');
        $isZipAllowed    = $config->upload->isExtensionAllowed('zip');
        $isEmptyAllowed  = $config->upload->isExtensionAllowed('');

        // THEN
        expect($isJpgAllowed)->toBeTrue();
        expect($isDotJpgAllowed)->toBeTrue();
        expect($isPngAllowed)->toBeTrue();
        expect($isExeAllowed)->toBeFalse();
        expect($isZipAllowed)->toBeFalse();
        expect($isEmptyAllowed)->toBeFalse();
    });

    it('always forbids UNSUPPORTED_EXTENSIONS even if present in allowed_extensions', function (): void {
        // GIVEN
        $config = StorageManagerConfig::fromArray(
            [
                'upload' => [
                    'allowed_extensions' => ['jpg', 'svg', 'xml', 'php', 'sh', 'exe'],
                ],
            ],
            []
        );

        // WHEN
        $isSvgAllowed   = $config->upload->isExtensionAllowed('svg');
        $isXmlAllowed   = $config->upload->isExtensionAllowed('xml');
        $isPhpAllowed   = $config->upload->isExtensionAllowed('php');
        $isPharAllowed  = $config->upload->isExtensionAllowed('phar');
        $isPhtmlAllowed = $config->upload->isExtensionAllowed('phtml');
        $isShAllowed    = $config->upload->isExtensionAllowed('sh');
        $isExeAllowed   = $config->upload->isExtensionAllowed('exe');
        $isBatAllowed   = $config->upload->isExtensionAllowed('bat');
        $isCmdAllowed   = $config->upload->isExtensionAllowed('cmd');
        $isJpgAllowed   = $config->upload->isExtensionAllowed('jpg');

        // THEN
        expect($isSvgAllowed)->toBeFalse();
        expect($isXmlAllowed)->toBeFalse();
        expect($isPhpAllowed)->toBeFalse();
        expect($isPharAllowed)->toBeFalse();
        expect($isPhtmlAllowed)->toBeFalse();
        expect($isShAllowed)->toBeFalse();
        expect($isExeAllowed)->toBeFalse();
        expect($isBatAllowed)->toBeFalse();
        expect($isCmdAllowed)->toBeFalse();
        expect($isJpgAllowed)->toBeTrue();
    });

    it('resolves direct drivers for non-scoped disks', function (): void {
        // GIVEN
        $filesystemsDisks = [
            'local' => ['driver' => 'local'],
            's3'    => ['driver' => 's3'],
        ];
        $config = StorageManagerConfig::fromArray([], [], $filesystemsDisks);

        // WHEN
        $localDriver = $config->disks->resolveDriver('local');
        $s3Driver    = $config->disks->resolveDriver('s3');

        // THEN
        expect($localDriver)->toBe('local');
        expect($s3Driver)->toBe('s3');
    });

    it('resolves underlying physical driver for scoped disks', function (): void {
        // GIVEN
        $filesystemsDisks = [
            'local_root'   => ['driver' => 'local'],
            'scoped_local' => [
                'driver' => 'scoped',
                'disk'   => 'local_root',
            ],
        ];
        $config = StorageManagerConfig::fromArray([], [], $filesystemsDisks);

        // WHEN
        $driver = $config->disks->resolveDriver('scoped_local');

        // THEN
        expect($driver)->toBe('local');
    });

    it('resolves multi-level scoped disks recursively', function (): void {
        // GIVEN
        $filesystemsDisks = [
            's3_main'        => ['driver' => 's3'],
            'scoped_level_1' => [
                'driver' => 'scoped',
                'disk'   => 's3_main',
            ],
            'scoped_level_2' => [
                'driver' => 'scoped',
                'disk'   => 'scoped_level_1',
            ],
        ];
        $config = StorageManagerConfig::fromArray([], [], $filesystemsDisks);

        // WHEN
        $driver = $config->disks->resolveDriver('scoped_level_2');

        // THEN
        expect($driver)->toBe('s3');
    });

    it('throws exception when scoped disk has no parent disk configured', function (): void {
        // GIVEN
        $filesystemsDisks = [
            'invalid_scoped' => [
                'driver' => 'scoped',
            ],
        ];
        $config = StorageManagerConfig::fromArray([], [], $filesystemsDisks);

        // WHEN
        $action = fn (): string => $config->disks->resolveDriver('invalid_scoped');

        // THEN
        expect($action)
            ->toThrow(\UnexpectedValueException::class, "Scoped disk 'invalid_scoped' is missing a valid parent 'disk' configuration.");
    });

    it('throws exception when scoped disk parent disk has no valid driver', function (): void {
        // GIVEN
        $filesystemsDisks = [
            'empty_parent'              => [],
            'scoped_with_broken_parent' => [
                'driver' => 'scoped',
                'disk'   => 'empty_parent',
            ],
        ];
        $config = StorageManagerConfig::fromArray([], [], $filesystemsDisks);

        // WHEN
        $action = fn (): string => $config->disks->resolveDriver('scoped_with_broken_parent');

        // THEN
        expect($action)
            ->toThrow(\UnexpectedValueException::class, "Parent disk 'empty_parent' of scoped disk 'scoped_with_broken_parent' has no valid driver configured.");
    });

    it('detects circular references in scoped disks', function (): void {
        // GIVEN
        $filesystemsDisks = [
            'scoped_a' => [
                'driver' => 'scoped',
                'disk'   => 'scoped_b',
            ],
            'scoped_b' => [
                'driver' => 'scoped',
                'disk'   => 'scoped_a',
            ],
        ];
        $config = StorageManagerConfig::fromArray([], [], $filesystemsDisks);

        // WHEN
        $action = fn (): string => $config->disks->resolveDriver('scoped_a');

        // THEN
        expect($action)
            ->toThrow(\UnexpectedValueException::class, "Circular reference detected in scoped disk 'scoped_b'.");
    });

    it('resolves scoped disks in getDisksMap', function (): void {
        // GIVEN
        $filesystemsDisks = [
            'physical_local' => ['driver' => 'local'],
            'scoped_disk'    => [
                'driver' => 'scoped',
                'disk'   => 'physical_local',
            ],
        ];

        $config = StorageManagerConfig::fromArray(
            [
                'disks' => [
                    'available' => ['scoped_disk', 'physical_local'],
                ],
            ],
            [],
            $filesystemsDisks
        );

        // WHEN
        $disksMap = $config->disks->getDisksMap();

        // THEN
        expect($disksMap)->toHaveKey('scoped_disk');
        expect($disksMap['scoped_disk']->driver)->toBe('local');
        expect($disksMap['scoped_disk']->name)->toBe('scoped_disk');
        expect($disksMap['physical_local']->driver)->toBe('local');
    });

    it('returns direct configured string when csp_nonce is a string and request has no matching attribute', function (): void {
        // GIVEN
        $request = HttpRequest::create('/sm', 'GET');
        Request::swap($request);
        $config = StorageManagerConfig::fromArray(
            [
                'frontend' => [
                    'csp_nonce' => 'direct-nonce-value',
                ],
            ],
            []
        );

        // WHEN
        $nonce = $config->frontend->getCspNonce();

        // THEN
        expect($nonce)->toBe('direct-nonce-value');
    });

    it('returns request attribute value when csp_nonce string matches an existing request attribute', function (): void {
        // GIVEN
        $request = HttpRequest::create('/sm', 'GET');
        $request->attributes->set('my_nonce_attr', 'nonce-from-request-attribute');
        Request::swap($request);
        $config = StorageManagerConfig::fromArray(
            [
                'frontend' => [
                    'csp_nonce' => 'my_nonce_attr',
                ],
            ],
            []
        );

        // WHEN
        $nonce = $config->frontend->getCspNonce();

        // THEN
        expect($nonce)->toBe('nonce-from-request-attribute');
    });

    it('returns result of closure when csp_nonce is a closure or callable', function (): void {
        // GIVEN
        $config = StorageManagerConfig::fromArray(
            [
                'frontend' => [
                    'csp_nonce' => fn (): string => 'closure-generated-nonce',
                ],
            ],
            []
        );

        // WHEN
        $nonce = $config->frontend->getCspNonce();

        // THEN
        expect($nonce)->toBe('closure-generated-nonce');
    });

    it('returns null when closure returns non-string or empty string', function (): void {
        // GIVEN
        $config = StorageManagerConfig::fromArray(
            [
                'frontend' => [
                    'csp_nonce' => fn (): string => '',
                ],
            ],
            []
        );

        // WHEN
        $nonce = $config->frontend->getCspNonce();

        // THEN
        expect($nonce)->toBeNull();
    });

    it('auto-detects csp_nonce from request attribute when csp_nonce config is null', function (): void {
        // GIVEN
        $request = HttpRequest::create('/sm', 'GET');
        $request->attributes->set('csp_nonce', 'auto-detected-snake-nonce');
        Request::swap($request);
        $config = StorageManagerConfig::fromArray(
            [
                'frontend' => [
                    'csp_nonce' => null,
                ],
            ],
            []
        );

        // WHEN
        $nonce = $config->frontend->getCspNonce();

        // THEN
        expect($nonce)->toBe('auto-detected-snake-nonce');
    });

    it('auto-detects cspNonce camelCase from request attribute when csp_nonce config is null', function (): void {
        // GIVEN
        $request = HttpRequest::create('/sm', 'GET');
        $request->attributes->set('cspNonce', 'auto-detected-camel-nonce');
        Request::swap($request);
        $config = StorageManagerConfig::fromArray(
            [
                'frontend' => [
                    'csp_nonce' => null,
                ],
            ],
            []
        );

        // WHEN
        $nonce = $config->frontend->getCspNonce();

        // THEN
        expect($nonce)->toBe('auto-detected-camel-nonce');
    });

    it('falls back to Vite cspNonce when csp_nonce config is null and no request attribute is set', function (): void {
        // GIVEN
        $request = HttpRequest::create('/sm', 'GET');
        Request::swap($request);
        Vite::useCspNonce('vite-fallback-nonce-999');
        $config = StorageManagerConfig::fromArray(
            [
                'frontend' => [
                    'csp_nonce' => null,
                ],
            ],
            []
        );

        // WHEN
        $nonce = $config->frontend->getCspNonce();

        // THEN
        expect($nonce)->toBe('vite-fallback-nonce-999');
    });

    it('throws exception when route prefix contains path traversal dots', function (): void {
        // GIVEN
        $invalidConfig = [
            'route' => [
                'prefix' => 'sm/../admin',
            ],
        ];

        // WHEN
        $action = fn (): StorageManagerConfig => StorageManagerConfig::fromArray($invalidConfig);

        // THEN
        expect($action)
            ->toThrow(\UnexpectedValueException::class, "Invalid route prefix 'sm/../admin'. Route prefix cannot contain '..', '\\', or '//'.");
    });

    it('throws exception when route prefix contains backslashes', function (): void {
        // GIVEN
        $invalidConfig = [
            'route' => [
                'prefix' => 'sm\admin',
            ],
        ];

        // WHEN
        $action = fn (): StorageManagerConfig => StorageManagerConfig::fromArray($invalidConfig);

        // THEN
        expect($action)
            ->toThrow(\UnexpectedValueException::class, "Invalid route prefix 'sm\\admin'. Route prefix cannot contain '..', '\\', or '//'.");
    });

    it('throws exception when route prefix contains double slashes', function (): void {
        // GIVEN
        $invalidConfig = [
            'route' => [
                'prefix' => '//sm',
            ],
        ];

        // WHEN
        $action = fn (): StorageManagerConfig => StorageManagerConfig::fromArray($invalidConfig);

        // THEN
        expect($action)
            ->toThrow(\UnexpectedValueException::class, "Invalid route prefix '//sm'. Route prefix cannot contain '..', '\\', or '//'.");
    });

    it('throws exception when trash folder contains path traversal dots', function (): void {
        // GIVEN
        $invalidConfig = [
            'trash' => [
                'folder' => '.trash/..',
            ],
        ];

        // WHEN
        $action = fn (): StorageManagerConfig => StorageManagerConfig::fromArray($invalidConfig);

        // THEN
        expect($action)
            ->toThrow(\UnexpectedValueException::class, "Invalid trash folder '.trash/..'. Trash folder cannot contain '..' or '\\'.");
    });

    it('throws exception when trash folder contains backslashes', function (): void {
        // GIVEN
        $invalidConfig = [
            'trash' => [
                'folder' => '.trash\sub',
            ],
        ];

        // WHEN
        $action = fn (): StorageManagerConfig => StorageManagerConfig::fromArray($invalidConfig);

        // THEN
        expect($action)
            ->toThrow(\UnexpectedValueException::class, "Invalid trash folder '.trash\\sub'. Trash folder cannot contain '..' or '\\'.");
    });
})->covers(StorageManagerConfig::class);
