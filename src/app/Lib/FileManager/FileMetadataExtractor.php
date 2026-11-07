<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use DateTimeImmutable;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

class FileMetadataExtractor
{
    private const int MAX_CHECKSUM_FILE_SIZE = 20971520; // 20 MB

    private const int HEADER_STREAM_BUFFER_SIZE = 524288; // 512 KB

    /** @var array<string> */
    private const array IMAGE_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tif', 'tiff', 'ico', 'avif',
    ];

    /** @var array<string> */
    private const array EXIF_EXTENSIONS = [
        'jpg', 'jpeg', 'tif', 'tiff', 'webp',
    ];

    public function __construct(
        private readonly FilePropertyExtractor $filePropertyExtractor,
        private readonly StorageGateway $storageGateway,
    ) {
    }

    public function extract(Disk $disk, Path $path): FilePathProperties
    {
        $baseProps = $this->filePropertyExtractor->fileProperties($disk, $path);
        if (! ($baseProps instanceof FilePathProperties)) {
            throw new \LogicException("Expected FilePathProperties for '{$path->value}'.");
        }

        $extension = strtolower($baseProps->extension);
        $mimeType  = $this->storageGateway->mimeType($disk, $path);
        $publicUrl = $this->storageGateway->publicUrl($disk, $path);

        $checksum   = $this->computeChecksum($disk, $path, $baseProps->size);
        $dimensions = null;
        $exif       = null;
        $rawExif    = null;

        $isImage = in_array($extension, self::IMAGE_EXTENSIONS, true)
            || ($mimeType !== null && str_starts_with($mimeType, 'image/'));

        if ($isImage) {
            $imageMeta  = $this->extractImageMetadata($disk, $path, $extension);
            $dimensions = $imageMeta['dimensions'];
            $exif       = $imageMeta['exif'];
            $rawExif    = $imageMeta['rawExif'];
            $mimeType   = $mimeType ?? $imageMeta['mimeType'];
        }

        return new FilePathProperties(
            path: $baseProps->path,
            basename: $baseProps->basename,
            dirname: $baseProps->dirname,
            extension: $extension,
            filename: $baseProps->filename,
            size: $baseProps->size,
            timestamp: $baseProps->timestamp,
            visibility: $baseProps->visibility,
            mimeType: $mimeType,
            publicUrl: $publicUrl,
            checksum: $checksum,
            dimensions: $dimensions,
            exif: $exif,
            rawExif: $rawExif,
        );
    }

    private function computeChecksum(Disk $disk, Path $path, int $size): ?string
    {
        if ($size > self::MAX_CHECKSUM_FILE_SIZE || $size < 0) {
            return null;
        }

        try {
            return $this->storageGateway->readStream($disk, $path)->hash('md5');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{
     *     dimensions: array{width: int, height: int, aspectRatio: string}|null,
     *     exif: array<string, mixed>|null,
     *     rawExif: array<string, string|int|float|bool>|null,
     *     mimeType: string|null
     * }
     */
    private function extractImageMetadata(Disk $disk, Path $path, string $extension): array
    {
        $localFilePath = $this->resolveLocalOrTempFile($disk, $path);
        if ($localFilePath === null) {
            return ['dimensions' => null, 'exif' => null, 'rawExif' => null, 'mimeType' => null];
        }

        $isTemp   = $localFilePath['isTemp'];
        $filePath = $localFilePath['path'];

        try {
            $dimensions   = $this->readDimensionsAndMime($filePath);
            $detectedMime = $dimensions['mimeType'];

            $exif    = null;
            $rawExif = null;

            if (in_array($extension, self::EXIF_EXTENSIONS, true) && function_exists('exif_read_data')) {
                $parsed  = $this->parseExif($filePath);
                $exif    = $parsed['exif'];
                $rawExif = $parsed['rawExif'];
            }

            return [
                'dimensions' => $dimensions['dimensions'],
                'exif'       => $exif,
                'rawExif'    => $rawExif,
                'mimeType'   => $detectedMime,
            ];
        } finally {
            if ($isTemp && file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * @return array{dimensions: array{width: int, height: int, aspectRatio: string}|null, mimeType: string|null}
     */
    private function readDimensionsAndMime(string $filePath): array
    {
        set_error_handler(static fn (): bool => true);
        $imageSize = getimagesize($filePath);
        restore_error_handler();

        if (! is_array($imageSize) || $imageSize[0] <= 0 || $imageSize[1] <= 0) {
            return ['dimensions' => null, 'mimeType' => null];
        }

        $width      = (int) $imageSize[0];
        $height     = (int) $imageSize[1];
        $dimensions = [
            'width'       => $width,
            'height'      => $height,
            'aspectRatio' => $this->calculateAspectRatio($width, $height),
        ];

        return ['dimensions' => $dimensions, 'mimeType' => $imageSize['mime']];
    }

    /**
     * @return array{path: string, isTemp: bool}|null
     */
    private function resolveLocalOrTempFile(Disk $disk, Path $path): ?array
    {
        $localPath = $this->storageGateway->localPath($disk, $path->value);
        if ($localPath !== null && file_exists($localPath)) {
            return ['path' => $localPath, 'isTemp' => false];
        }

        try {
            $tempFile = tempnam(sys_get_temp_dir(), 'sm_img_');
            if ($tempFile === false) {
                return null;
            }

            $this->storageGateway->readStream($disk, $path)->copyToPath($tempFile, self::HEADER_STREAM_BUFFER_SIZE);

            return ['path' => $tempFile, 'isTemp' => true];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{
     *     exif: array<string, mixed>|null,
     *     rawExif: array<string, string|int|float|bool>|null
     * }
     */
    private function parseExif(string $filePath): array
    {
        try {
            set_error_handler(static fn (): bool => true);
            $data = exif_read_data($filePath, 'ANY_TAG', true, false);
            restore_error_handler();

            if (! is_array($data) || empty($data)) {
                return ['exif' => null, 'rawExif' => null];
            }

            /** @var array<string, mixed> $data */
            $flatData = $this->flattenExifData($data);

            return [
                'exif'    => $this->buildStructuredExif($flatData),
                'rawExif' => $this->filterRawExif($flatData),
            ];
        } catch (\Throwable) {
            return ['exif' => null, 'rawExif' => null];
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function flattenExifData(array $data): array
    {
        /** @var array<string, mixed> $flatData */
        $flatData = [];
        foreach ($data as $section => $values) {
            if (is_array($values)) {
                foreach ($values as $k => $v) {
                    $flatData[(string) $k] = $v;
                }
            } else {
                $flatData[$section] = $values;
            }
        }

        return $flatData;
    }

    /**
     * @param  array<string, mixed>  $flatData
     * @return array<string, mixed>
     */
    private function buildStructuredExif(array $flatData): array
    {
        return array_merge(
            $this->buildCameraExif($flatData),
            $this->buildShotExif($flatData),
        );
    }

    /**
     * @param  array<string, mixed>  $flatData
     * @return array{make: string|null, model: string|null, lens: string|null, software: string|null, artist: string|null, copyright: string|null}
     */
    private function buildCameraExif(array $flatData): array
    {
        $lensRaw = $flatData['LensModel'] ?? $flatData['UndefinedTag:0xA434'] ?? null;

        return [
            'make'      => $this->cleanString($flatData['Make'] ?? null),
            'model'     => $this->cleanString($flatData['Model'] ?? null),
            'lens'      => $this->cleanString($lensRaw),
            'software'  => $this->cleanString($flatData['Software'] ?? null),
            'artist'    => $this->cleanString($flatData['Artist'] ?? null),
            'copyright' => $this->cleanString($flatData['Copyright'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $flatData
     * @return array<string, mixed>
     */
    private function buildShotExif(array $flatData): array
    {
        $fNumberRaw = $this->findFirstValue($flatData, ['FNumber', 'ApertureFNumber']);
        $isoRaw     = $this->findFirstValue($flatData, ['ISOSpeedRatings', 'PhotographicSensitivity']);
        $dateRaw    = $this->findFirstValue($flatData, ['DateTimeOriginal', 'DateTimeDigitized', 'DateTime']);

        return [
            'aperture'          => $this->parseAperture($fNumberRaw),
            'exposureTime'      => $this->parseExposureTime($flatData['ExposureTime'] ?? null),
            'iso'               => $this->parseIso($isoRaw),
            'focalLength'       => $this->parseFocalLength($flatData['FocalLength'] ?? null),
            'focalLengthIn35mm' => $this->parseFocalLength($flatData['FocalLengthIn35mmFilm'] ?? null),
            'flash'             => $this->parseFlash($flatData['Flash'] ?? null),
            'whiteBalance'      => $this->parseWhiteBalance($flatData['WhiteBalance'] ?? null),
            'meteringMode'      => $this->parseMeteringMode($flatData['MeteringMode'] ?? null),
            'dateTaken'         => $this->parseExifDate($dateRaw),
            'gps'               => $this->parseGps($flatData),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string>  $keys
     */
    private function findFirstValue(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                return $data[$key];
            }
        }

        return null;
    }

    private function cleanString(mixed $val): ?string
    {
        if ($val === null || ! is_string($val)) {
            return null;
        }

        $trimmed = trim($val);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function parseAperture(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }

        if (is_numeric($val)) {
            return 'f/' . round((float) $val, 1);
        }

        if (is_string($val) && str_contains($val, '/')) {
            $parts = explode('/', $val);
            $num   = (float) ($parts[0] ?? 0);
            $den   = (float) ($parts[1] ?? 1);
            if ($den > 0) {
                return 'f/' . round($num / $den, 1);
            }
        }

        return is_string($val) ? 'f/' . trim($val) : null;
    }

    private function parseExposureTime(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }

        if (is_string($val) && str_contains($val, '/')) {
            return $this->formatFractionExposureTime($val);
        }

        if (is_numeric($val)) {
            $floatVal = (float) $val;
            if ($floatVal < 1.0 && $floatVal > 0) {
                return '1/' . (int) round(1 / $floatVal) . 's';
            }

            return round($floatVal, 2) . 's';
        }

        return is_string($val) ? trim($val) : null;
    }

    private function formatFractionExposureTime(string $val): ?string
    {
        $parts = explode('/', $val);
        $num   = (float) ($parts[0] ?? 0);
        $den   = (float) ($parts[1] ?? 1);

        if ($den <= 0) {
            return null;
        }

        if ($num === 1.0) {
            return '1/' . (int) $den . 's';
        }
        if ($num < $den && $num > 0) {
            return '1/' . (int) round($den / $num) . 's';
        }

        return round($num / $den, 2) . 's';
    }

    private function parseIso(mixed $val): ?int
    {
        if (is_int($val)) {
            return $val;
        }

        if (is_numeric($val)) {
            return (int) $val;
        }

        if (is_array($val) && isset($val[0]) && is_numeric($val[0])) {
            return (int) $val[0];
        }

        return null;
    }

    private function parseFocalLength(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }

        if (is_numeric($val)) {
            return round((float) $val, 1) . ' mm';
        }

        if (is_string($val) && str_contains($val, '/')) {
            $parts = explode('/', $val);
            $num   = (float) ($parts[0] ?? 0);
            $den   = (float) ($parts[1] ?? 1);
            if ($den > 0) {
                return round($num / $den, 1) . ' mm';
            }
        }

        return is_string($val) ? trim($val) . ' mm' : null;
    }

    private function parseFlash(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }

        if (is_numeric($val)) {
            return ((int) $val & 1) === 1 ? 'Fired' : 'Did not fire';
        }

        return is_string($val) ? trim($val) : null;
    }

    private function parseWhiteBalance(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }

        if (is_numeric($val)) {
            return (int) $val === 0 ? 'Auto' : 'Manual';
        }

        return is_string($val) ? trim($val) : null;
    }

    private function parseMeteringMode(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }

        $modes = [
            0 => 'Unknown',
            1 => 'Average',
            2 => 'Center-weighted average',
            3 => 'Spot',
            4 => 'Multi-spot',
            5 => 'Pattern',
            6 => 'Partial',
        ];

        if (is_numeric($val) && isset($modes[(int) $val])) {
            return $modes[(int) $val];
        }

        return is_string($val) ? trim($val) : null;
    }

    private function parseExifDate(mixed $val): ?string
    {
        if (! is_string($val)) {
            return null;
        }

        $val = trim($val);
        if ($val === '') {
            return null;
        }

        if (preg_match('/^(\d{4}):(\d{2}):(\d{2}) (\d{2}:\d{2}:\d{2})$/', $val, $matches)) {
            return "{$matches[1]}-{$matches[2]}-{$matches[3]} {$matches[4]}";
        }

        try {
            $dt = new DateTimeImmutable($val);

            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return $val;
        }
    }

    /**
     * @param  array<string, mixed>  $flatData
     * @return array{latitude: float, longitude: float, altitude: float|null, mapsUrl: string, googleMapsUrl: string}|null
     */
    private function parseGps(array $flatData): ?array
    {
        $latRef = $flatData['GPSLatitudeRef']  ?? null;
        $lat    = $flatData['GPSLatitude']     ?? null;
        $lonRef = $flatData['GPSLongitudeRef'] ?? null;
        $lon    = $flatData['GPSLongitude']    ?? null;

        if (! is_string($latRef) || ! is_array($lat) || ! is_string($lonRef) || ! is_array($lon)) {
            return null;
        }

        $latitude  = $this->convertGpsCoordinate($lat, $latRef);
        $longitude = $this->convertGpsCoordinate($lon, $lonRef);

        if ($latitude === null || $longitude === null) {
            return null;
        }

        $altitude      = $this->parseGpsAltitude($flatData['GPSAltitude'] ?? null);
        $mapsUrl       = "https://www.openstreetmap.org/?mlat={$latitude}&mlon={$longitude}#map=16/{$latitude}/{$longitude}";
        $googleMapsUrl = "https://www.google.com/maps?q={$latitude},{$longitude}";

        return [
            'latitude'      => $latitude,
            'longitude'     => $longitude,
            'altitude'      => $altitude,
            'mapsUrl'       => $mapsUrl,
            'googleMapsUrl' => $googleMapsUrl,
        ];
    }

    private function parseGpsAltitude(mixed $altRaw): ?float
    {
        if ($altRaw === null) {
            return null;
        }

        if (is_string($altRaw) && str_contains($altRaw, '/')) {
            $parts = explode('/', $altRaw);
            $num   = (float) ($parts[0] ?? 0);
            $den   = (float) ($parts[1] ?? 1);
            if ($den > 0) {
                return round($num / $den, 1);
            }
        } elseif (is_numeric($altRaw)) {
            return round((float) $altRaw, 1);
        }

        return null;
    }

    /**
     * @param  array<mixed>  $coord
     */
    private function convertGpsCoordinate(array $coord, string $ref): ?float
    {
        $decimal = 0.0;
        foreach ([0, 1, 2] as $index) {
            $value = $this->parseCoordinateComponent($coord[$index] ?? null);
            if ($value === null) {
                return null;
            }

            if ($index === 0) {
                $decimal += $value;
            } elseif ($index === 1) {
                $decimal += $value / 60;
            } else {
                $decimal += $value / 3600;
            }
        }

        if (in_array(strtoupper($ref), ['S', 'W'], true)) {
            $decimal = -$decimal;
        }

        return round($decimal, 6);
    }

    private function parseCoordinateComponent(mixed $part): ?float
    {
        if (! is_string($part) && ! is_numeric($part)) {
            return null;
        }

        $partStr = (string) $part;
        if (str_contains($partStr, '/')) {
            $split = explode('/', $partStr);
            $num   = (float) ($split[0] ?? 0);
            $den   = (float) ($split[1] ?? 1);
            if ($den === 0.0) {
                return null;
            }

            return $num / $den;
        }

        return (float) $partStr;
    }

    /**
     * @param  array<string, mixed>  $flatData
     * @return array<string, string|int|float|bool>
     */
    private function filterRawExif(array $flatData): array
    {
        /** @var array<string, string|int|float|bool> $filtered */
        $filtered = [];

        foreach ($flatData as $key => $value) {
            if (is_scalar($value)) {
                if (is_string($value) && (strlen($value) > 500 || ! mb_check_encoding($value, 'UTF-8'))) {
                    continue;
                }
                $filtered[$key] = $value;
            }
        }

        ksort($filtered);

        return $filtered;
    }

    private function calculateAspectRatio(int $width, int $height): string
    {
        if ($width <= 0 || $height <= 0) {
            return '1:1';
        }

        $ratio       = (float) $width / (float) $height;
        $commonMatch = $this->matchCommonRatio($ratio);
        if ($commonMatch !== null) {
            return $commonMatch;
        }

        $gcd   = $this->gcd($width, $height);
        $wSimp = (int) ($width / $gcd);
        $hSimp = (int) ($height / $gcd);

        if ($wSimp <= 20 && $hSimp <= 20) {
            return "{$wSimp}:{$hSimp}";
        }

        return round($ratio, 2) . ':1';
    }

    private function matchCommonRatio(float $ratio): ?string
    {
        $commonRatios = [
            '16:9'  => 16 / 9,
            '16:10' => 16 / 10,
            '4:3'   => 4  / 3,
            '3:2'   => 3  / 2,
            '1:1'   => 1.0,
            '9:16'  => 9  / 16,
            '2:3'   => 2  / 3,
            '3:4'   => 3  / 4,
            '21:9'  => 21 / 9,
        ];

        foreach ($commonRatios as $label => $val) {
            if (abs($ratio - $val) < 0.02) {
                return $label;
            }
        }

        return null;
    }

    private function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            $t = $b;
            $b = $a % $b;
            $a = $t;
        }

        return $a;
    }
}
