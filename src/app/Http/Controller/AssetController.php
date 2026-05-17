<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetController extends Controller
{
    private const IMAGES_PATH = __DIR__ . '/../../../../resources/images';

    private const CSS_PATH = __DIR__ . '/../../../../resources/css';

    private const JS_PATH = __DIR__ . '/../../../../resources/js';

    public function __construct(
        private readonly PathNormalizer $pathNormalizer,
    ) {
    }

    public function __invoke(string $dir, string $file): BinaryFileResponse
    {
        $assetPath = $this->getAssetPath($dir, $file);

        if ($assetPath === null) {
            abort(404);
        }

        return response()->file($assetPath)->setPublic()->setMaxAge(31536000);
    }

    private function getAssetPath(string $dir, string $file): ?string
    {
        $assetPathDir = $this->getAssetPathDir($dir);

        if ($assetPathDir === null) {
            return null;
        }

        $assetPath = $assetPathDir . $this->pathNormalizer->normalizePath(
            str_replace(['..', '/', '\\'], '', $file)
        );

        return realpath($assetPath) ?: null;
    }

    private function getAssetPathDir(string $dir): ?string
    {
        return match ($dir) {
            'css'     => self::CSS_PATH,
            'js'      => self::JS_PATH,
            'images'  => self::IMAGES_PATH,
            default   => null
        };
    }
}
