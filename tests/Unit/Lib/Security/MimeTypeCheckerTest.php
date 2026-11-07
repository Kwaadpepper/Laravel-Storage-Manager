<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\Security\MimeTypeChecker;

describe('MimeTypeChecker', function (): void {
    it('detects mime type of an existing file', function (): void {
        // GIVEN
        $checker = new MimeTypeChecker();
        $tmpFile = tempnam(sys_get_temp_dir(), 'mtc_');
        expect($tmpFile)->not()->toBeFalse();

        file_put_contents((string) $tmpFile, 'Hello plain text file');

        try {
            // WHEN
            $mime = $checker->detectMimeType((string) $tmpFile);

            // THEN
            expect($mime)->toBe('text/plain');
        } finally {
            if (file_exists((string) $tmpFile)) {
                unlink((string) $tmpFile);
            }
        }
    });

    it('returns application/octet-stream for non-existent or unreadable file', function (): void {
        // GIVEN
        $checker = new MimeTypeChecker();

        // WHEN
        $mime = $checker->detectMimeType('/non/existent/path/file.xyz');

        // THEN
        expect($mime)->toBe('application/octet-stream');
    });

    it('detects buffer mime type for in-memory content', function (): void {
        // GIVEN
        $checker  = new MimeTypeChecker();
        $pngBytes = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15c4";

        // WHEN
        $textMime = $checker->detectBufferMimeType('Just some text');
        $pngMime  = $checker->detectBufferMimeType($pngBytes);

        // THEN
        expect($textMime)->toBe('text/plain');
        expect($pngMime)->toBe('image/png');
    });

    it('identifies dangerous mime types correctly', function (): void {
        // GIVEN
        $checker = new MimeTypeChecker();

        // WHEN
        $r1  = $checker->isDangerousMime('text/x-php');
        $r2  = $checker->isDangerousMime('application/x-php');
        $r3  = $checker->isDangerousMime('application/x-executable');
        $r4  = $checker->isDangerousMime('application/x-shellscript');
        $r5  = $checker->isDangerousMime('image/svg+xml');
        $r6  = $checker->isDangerousMime('application/xml');
        $r7  = $checker->isDangerousMime('  TEXT/X-PHP  ');
        $r8  = $checker->isDangerousMime('image/jpeg');
        $r9  = $checker->isDangerousMime('image/png');
        $r10 = $checker->isDangerousMime('application/pdf');
        $r11 = $checker->isDangerousMime('text/plain');

        // THEN
        expect($r1)->toBeTrue();
        expect($r2)->toBeTrue();
        expect($r3)->toBeTrue();
        expect($r4)->toBeTrue();
        expect($r5)->toBeTrue();
        expect($r6)->toBeTrue();
        expect($r7)->toBeTrue();
        expect($r8)->toBeFalse();
        expect($r9)->toBeFalse();
        expect($r10)->toBeFalse();
        expect($r11)->toBeFalse();
    });

    it('rejects unsupported and dangerous extensions directly', function (): void {
        // GIVEN
        $checker = new MimeTypeChecker();

        // WHEN
        $r1 = $checker->isMimeAllowedForExtension('svg', 'image/svg+xml');
        $r2 = $checker->isMimeAllowedForExtension('xml', 'application/xml');
        $r3 = $checker->isMimeAllowedForExtension('php', 'text/plain');
        $r4 = $checker->isMimeAllowedForExtension('exe', 'application/octet-stream');
        $r5 = $checker->isMimeAllowedForExtension('sh', 'text/x-shellscript');

        // THEN
        expect($r1)->toBeFalse();
        expect($r2)->toBeFalse();
        expect($r3)->toBeFalse();
        expect($r4)->toBeFalse();
        expect($r5)->toBeFalse();
    });

    it('rejects files with dangerous mime even if extension looks safe (anti-spoofing)', function (): void {
        // GIVEN
        $checker = new MimeTypeChecker();

        // WHEN
        $r1 = $checker->isMimeAllowedForExtension('jpg', 'text/x-php');
        $r2 = $checker->isMimeAllowedForExtension('png', 'application/x-php');
        $r3 = $checker->isMimeAllowedForExtension('pdf', 'application/x-executable');
        $r4 = $checker->isMimeAllowedForExtension('png', 'image/svg+xml');

        // THEN
        expect($r1)->toBeFalse();
        expect($r2)->toBeFalse();
        expect($r3)->toBeFalse();
        expect($r4)->toBeFalse();
    });

    it('validates matching extensions and mime types', function (): void {
        // GIVEN
        $checker = new MimeTypeChecker();

        // WHEN
        $r1 = $checker->isMimeAllowedForExtension('jpg', 'image/jpeg');
        $r2 = $checker->isMimeAllowedForExtension('.jpeg', 'image/jpeg');
        $r3 = $checker->isMimeAllowedForExtension('png', 'image/png');
        $r4 = $checker->isMimeAllowedForExtension('pdf', 'application/pdf');
        $r5 = $checker->isMimeAllowedForExtension('zip', 'application/zip');
        $r6 = $checker->isMimeAllowedForExtension('csv', 'text/plain');
        $r7 = $checker->isMimeAllowedForExtension('tsv', 'text/plain');
        $r8 = $checker->isMimeAllowedForExtension('txt', 'text/plain');
        $r9 = $checker->isMimeAllowedForExtension('md', 'text/plain');

        // THEN
        expect($r1)->toBeTrue();
        expect($r2)->toBeTrue();
        expect($r3)->toBeTrue();
        expect($r4)->toBeTrue();
        expect($r5)->toBeTrue();
        expect($r6)->toBeTrue();
        expect($r7)->toBeTrue();
        expect($r8)->toBeTrue();
        expect($r9)->toBeTrue();
    });

    it('rejects mismatched extensions and mime types', function (): void {
        // GIVEN
        $checker = new MimeTypeChecker();

        // WHEN
        $r1 = $checker->isMimeAllowedForExtension('jpg', 'application/pdf');
        $r2 = $checker->isMimeAllowedForExtension('pdf', 'image/png');
        $r3 = $checker->isMimeAllowedForExtension('jpg', 'text/plain');

        // THEN
        expect($r1)->toBeFalse();
        expect($r2)->toBeFalse();
        expect($r3)->toBeFalse();
    });

    it('allows unknown extensions if mime is not dangerous and no known mime is expected', function (): void {
        // GIVEN
        $checker = new MimeTypeChecker();

        // WHEN
        $result = $checker->isMimeAllowedForExtension('mycustomext', 'application/octet-stream');

        // THEN
        expect($result)->toBeTrue();
    });

    it('guesses mime type from extension using overrides and fallback', function (): void {
        // GIVEN
        $checker = new MimeTypeChecker();

        // WHEN
        $r1 = $checker->guessMimeTypeFromExtension('mp4');
        $r2 = $checker->guessMimeTypeFromExtension('.MP4');
        $r3 = $checker->guessMimeTypeFromExtension('mp3');
        $r4 = $checker->guessMimeTypeFromExtension('png');
        $r5 = $checker->guessMimeTypeFromExtension('unknown_ext');
        $r6 = $checker->guessMimeTypeFromExtension('', 'text/plain');

        // THEN
        expect($r1)->toBe('video/mp4');
        expect($r2)->toBe('video/mp4');
        expect($r3)->toBe('audio/mpeg');
        expect($r4)->toBe('image/png');
        expect($r5)->toBe('application/octet-stream');
        expect($r6)->toBe('text/plain');
    });
});
