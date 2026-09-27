<?php

declare(strict_types=1);

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileMetadataExtractor;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;

describe('FileMetadataExtractorTest', function (): void {
    it('extracts text file metadata correctly', function (): void {
        $diskName = 'test_meta_disk';
        $path     = new Path('/documents/note.txt');
        $content  = 'Hello world!';

        Storage::fake($diskName);
        Storage::disk($diskName)->put($path->value, $content);

        $disk      = new Disk(driver: 'local', name: $diskName, throw: false, report: false);
        $extractor = new FileMetadataExtractor();

        $dto = $extractor->extract($disk, $path);

        expect($dto->path)->toBe('/documents/note.txt');
        expect($dto->basename)->toBe('note.txt');
        expect($dto->dirname)->toBe('/documents');
        expect($dto->extension)->toBe('txt');
        expect($dto->size)->toBe(strlen($content));
        expect($dto->checksum)->toBe(md5($content));
        expect($dto->visibility)->toBe(PathVisibility::PUBLIC->value);
        expect($dto->isFile)->toBe(true);
        expect($dto->isDirectory)->toBe(false);
        expect($dto->dimensions)->toBeNull();
        expect($dto->exif)->toBeNull();
    });

    it('extracts image dimensions and aspect ratio for png', function (): void {
        $diskName = 'test_meta_disk';
        $path     = new Path('/images/photo.png');

        $img = imagecreatetruecolor(1920, 1080);
        ob_start();
        imagepng($img);
        $pngData = (string) ob_get_clean();
        imagedestroy($img);

        Storage::fake($diskName);
        Storage::disk($diskName)->put($path->value, $pngData);

        $disk      = new Disk(driver: 'local', name: $diskName, throw: false, report: false);
        $extractor = new FileMetadataExtractor();

        $dto = $extractor->extract($disk, $path);

        expect($dto->extension)->toBe('png');
        expect($dto->dimensions)->not->toBeNull();
        expect($dto->dimensions['width'])->toBe(1920);
        expect($dto->dimensions['height'])->toBe(1080);
        expect($dto->dimensions['aspectRatio'])->toBe('16:9');
    });

    it('handles files larger than 20MB by skipping checksum', function (): void {
        $diskName = 'test_meta_disk';
        $path     = new Path('/big.bin');

        $mockDisk = \Mockery::mock(Filesystem::class);
        $mockDisk->shouldReceive('size')->with('/big.bin')->andReturn(25000000);
        $mockDisk->shouldReceive('lastModified')->with('/big.bin')->andReturn(1700000000);
        $mockDisk->shouldReceive('getVisibility')->with('/big.bin')->andReturn('public');
        $mockDisk->shouldReceive('mimeType')->with('/big.bin')->andReturn('application/octet-stream');
        $mockDisk->shouldReceive('url')->with('big.bin')->andReturn('http://localhost/big.bin');

        Storage::shouldReceive('disk')->with($diskName)->andReturn($mockDisk);

        $disk      = new Disk(driver: 'local', name: $diskName, throw: false, report: false);
        $extractor = new FileMetadataExtractor();

        $dto = $extractor->extract($disk, $path);

        expect($dto->size)->toBe(25000000);
        expect($dto->checksum)->toBeNull();
    });

    it('correctly parses GPS coordinates into decimal values and map links', function (): void {
        $extractor = new FileMetadataExtractor();
        $ref       = new \ReflectionClass($extractor);
        $method    = $ref->getMethod('parseGps');
        $method->setAccessible(true);

        $flatData = [
            'GPSLatitudeRef'  => 'N',
            'GPSLatitude'     => ['48/1', '51/1', '24/1'],
            'GPSLongitudeRef' => 'E',
            'GPSLongitude'    => ['2/1', '21/1', '7/1'],
            'GPSAltitude'     => '350/10',
        ];

        /** @var array{latitude: float, longitude: float, altitude: float|null, mapsUrl: string, googleMapsUrl: string} $gps */
        $gps = $method->invoke($extractor, $flatData);

        expect($gps)->not->toBeNull();
        expect($gps['latitude'])->toBe(48.856667);
        expect($gps['longitude'])->toBe(2.351944);
        expect($gps['altitude'])->toBe(35.0);
        expect($gps['mapsUrl'])->toContain('48.856667');
        expect($gps['googleMapsUrl'])->toContain('48.856667,2.351944');
    });

    it('correctly handles South and West GPS coordinates', function (): void {
        $extractor = new FileMetadataExtractor();
        $ref       = new \ReflectionClass($extractor);
        $method    = $ref->getMethod('parseGps');
        $method->setAccessible(true);

        $flatData = [
            'GPSLatitudeRef'  => 'S',
            'GPSLatitude'     => ['22/1', '54/1', '0/1'],
            'GPSLongitudeRef' => 'W',
            'GPSLongitude'    => ['43/1', '12/1', '0/1'],
        ];

        /** @var array{latitude: float, longitude: float, altitude: float|null, mapsUrl: string, googleMapsUrl: string} $gps */
        $gps = $method->invoke($extractor, $flatData);

        expect($gps)->not->toBeNull();
        expect($gps['latitude'])->toBe(-22.9);
        expect($gps['longitude'])->toBe(-43.2);
    });

    it('formats aperture, exposure time, focal length, and flash correctly', function (): void {
        $extractor = new FileMetadataExtractor();
        $ref       = new \ReflectionClass($extractor);

        $parseAperture = $ref->getMethod('parseAperture');
        $parseAperture->setAccessible(true);
        expect($parseAperture->invoke($extractor, '28/10'))->toBe('f/2.8');
        expect($parseAperture->invoke($extractor, 4.0))->toBe('f/4');

        $parseExposure = $ref->getMethod('parseExposureTime');
        $parseExposure->setAccessible(true);
        expect($parseExposure->invoke($extractor, '1/250'))->toBe('1/250s');
        expect($parseExposure->invoke($extractor, '2/1'))->toBe('2s');
        expect($parseExposure->invoke($extractor, 0.005))->toBe('1/200s');

        $parseFocal = $ref->getMethod('parseFocalLength');
        $parseFocal->setAccessible(true);
        expect($parseFocal->invoke($extractor, '500/10'))->toBe('50 mm');
        expect($parseFocal->invoke($extractor, 85))->toBe('85 mm');

        $parseFlash = $ref->getMethod('parseFlash');
        $parseFlash->setAccessible(true);
        expect($parseFlash->invoke($extractor, 1))->toBe('Fired');
        expect($parseFlash->invoke($extractor, 0))->toBe('Did not fire');

        $parseWhite = $ref->getMethod('parseWhiteBalance');
        $parseWhite->setAccessible(true);
        expect($parseWhite->invoke($extractor, 0))->toBe('Auto');
        expect($parseWhite->invoke($extractor, 1))->toBe('Manual');

        $parseDate = $ref->getMethod('parseExifDate');
        $parseDate->setAccessible(true);
        expect($parseDate->invoke($extractor, '2024:06:15 14:32:10'))->toBe('2024-06-15 14:32:10');
    });
});
