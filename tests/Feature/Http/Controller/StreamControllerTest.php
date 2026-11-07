<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;

describe('StreamController', function (): void {
    it('streams an existing file completely with 200 OK and inline disposition', function (): void {
        // GIVEN
        Storage::fake('local');
        $content = str_repeat('0123456789', 100);
        Storage::disk('local')->put('videos/sample.mp4', $content);

        // WHEN
        $response = $this->get(route('storage-manager.stream', [
            'disk' => 'local',
            'path' => 'videos/sample.mp4',
        ]));

        // THEN
        $response->assertStatus(200);
        $response->assertHeader('Accept-Ranges', 'bytes');
        $response->assertHeader('Content-Type', 'video/mp4');
        $response->assertHeader('Content-Length', '1000');
        expect($response->headers->get('Content-Disposition'))->toContain('inline; filename="sample.mp4"');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        expect($output)->toBe($content);
    });

    it('streams partial content with 206 Partial Content when Range header is provided', function (): void {
        // GIVEN
        Storage::fake('local');
        $content = '0123456789ABCDEF';
        Storage::disk('local')->put('videos/sample.mp4', $content);

        // WHEN
        $response = $this->get(
            route('storage-manager.stream', [
                'disk' => 'local',
                'path' => 'videos/sample.mp4',
            ]),
            ['Range' => 'bytes=0-9']
        );

        // THEN
        $response->assertStatus(206);
        $response->assertHeader('Accept-Ranges', 'bytes');
        $response->assertHeader('Content-Range', 'bytes 0-9/16');
        $response->assertHeader('Content-Length', '10');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        expect($output)->toBe('0123456789');
    });

    it('handles open-ended range requests', function (): void {
        // GIVEN
        Storage::fake('local');
        $content = '0123456789ABCDEF';
        Storage::disk('local')->put('videos/sample.mp4', $content);

        // WHEN
        $response = $this->get(
            route('storage-manager.stream', [
                'disk' => 'local',
                'path' => 'videos/sample.mp4',
            ]),
            ['Range' => 'bytes=10-']
        );

        // THEN
        $response->assertStatus(206);
        $response->assertHeader('Content-Range', 'bytes 10-15/16');
        $response->assertHeader('Content-Length', '6');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        expect($output)->toBe('ABCDEF');
    });

    it('handles suffix range requests', function (): void {
        // GIVEN
        Storage::fake('local');
        $content = '0123456789ABCDEF';
        Storage::disk('local')->put('videos/sample.mp4', $content);

        // WHEN
        $response = $this->get(
            route('storage-manager.stream', [
                'disk' => 'local',
                'path' => 'videos/sample.mp4',
            ]),
            ['Range' => 'bytes=-6']
        );

        // THEN
        $response->assertStatus(206);
        $response->assertHeader('Content-Range', 'bytes 10-15/16');
        $response->assertHeader('Content-Length', '6');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        expect($output)->toBe('ABCDEF');
    });

    it('returns 416 Range Not Satisfiable when range is invalid', function (): void {
        // GIVEN
        Storage::fake('local');
        $content = '0123456789ABCDEF';
        Storage::disk('local')->put('videos/sample.mp4', $content);

        // WHEN
        $response = $this->get(
            route('storage-manager.stream', [
                'disk' => 'local',
                'path' => 'videos/sample.mp4',
            ]),
            ['Range' => 'bytes=999-']
        );

        // THEN
        $response->assertStatus(416);
        $response->assertHeader('Content-Range', 'bytes */16');
    });

    it('returns 404 when file does not exist', function (): void {
        // GIVEN
        Storage::fake('local');

        // WHEN
        $response = $this->get(route('storage-manager.stream', [
            'disk' => 'local',
            'path' => 'videos/missing.mp4',
        ]));

        // THEN
        $response->assertStatus(404);
    });

    it('streams empty files gracefully with 200 OK', function (): void {
        // GIVEN
        Storage::fake('local');
        Storage::disk('local')->put('videos/empty.mp4', '');

        // WHEN
        $response = $this->get(route('storage-manager.stream', [
            'disk' => 'local',
            'path' => 'videos/empty.mp4',
        ]));

        // THEN
        $response->assertStatus(200);
        $response->assertHeader('Content-Length', '0');
    });

    it('can download files via DownloadController without memory error', function (): void {
        // GIVEN
        Storage::fake('local');
        $content = str_repeat('X', 5000);
        Storage::disk('local')->put('docs/sample.txt', $content);

        // WHEN
        $response = $this->get(route('storage-manager.download', [
            'disk' => 'local',
            'path' => 'docs/sample.txt',
        ]));

        // THEN
        $response->assertStatus(200);
        expect($response->headers->get('Content-Disposition'))->toContain('attachment');
        expect($response->headers->get('Content-Disposition'))->toContain('sample.txt');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        expect($output)->toBe($content);
    });
});
