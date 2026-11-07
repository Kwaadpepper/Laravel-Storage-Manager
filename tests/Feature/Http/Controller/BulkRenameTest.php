<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Event\BulkRenamed;
use Kwaadpepper\LaravelStorageManager\Event\FileRenamed;

describe('BulkRename', function (): void {
    it('can rename multiple files in batch', function (): void {
        // GIVEN
        Event::fake([BulkRenamed::class, FileRenamed::class]);
        Storage::fake('local');
        Storage::disk('local')->put('photos/img1.jpg', 'content 1');
        Storage::disk('local')->put('photos/img2.jpg', 'content 2');

        // WHEN
        $response = $this->putJson(route('storage-manager.api.fm.bulk-rename'), [
            'disk'    => 'local',
            'renames' => [
                ['from' => '/photos/img1.jpg', 'to' => 'photo_01.jpg'],
                ['from' => '/photos/img2.jpg', 'to' => 'photo_02.jpg'],
            ],
        ]);

        // THEN
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'renamed',
                ],
            ]);

        expect(Storage::disk('local')->exists('photos/photo_01.jpg'))->toBeTrue();
        expect(Storage::disk('local')->exists('photos/photo_02.jpg'))->toBeTrue();
        expect(Storage::disk('local')->exists('photos/img1.jpg'))->toBeFalse();
        expect(Storage::disk('local')->exists('photos/img2.jpg'))->toBeFalse();

        Event::assertDispatched(BulkRenamed::class);
        Event::assertDispatched(FileRenamed::class, 2);
    });

    it('handles cyclic / shifting renames safely', function (): void {
        // GIVEN
        Storage::fake('local');
        Storage::disk('local')->put('items/a.txt', 'AAA');
        Storage::disk('local')->put('items/b.txt', 'BBB');

        // WHEN
        $response = $this->putJson(route('storage-manager.api.fm.bulk-rename'), [
            'disk'    => 'local',
            'renames' => [
                ['from' => '/items/a.txt', 'to' => 'b.txt'],
                ['from' => '/items/b.txt', 'to' => 'c.txt'],
            ],
        ]);

        // THEN
        $response->assertStatus(200);
        expect(Storage::disk('local')->exists('items/b.txt'))->toBeTrue();
        expect(Storage::disk('local')->get('items/b.txt'))->toBe('AAA');
        expect(Storage::disk('local')->exists('items/c.txt'))->toBeTrue();
        expect(Storage::disk('local')->get('items/c.txt'))->toBe('BBB');
        expect(Storage::disk('local')->exists('items/a.txt'))->toBeFalse();
    });

    it('rejects bulk rename when source file does not exist', function (): void {
        // GIVEN
        Storage::fake('local');

        // WHEN
        $response = $this->putJson(route('storage-manager.api.fm.bulk-rename'), [
            'disk'    => 'local',
            'renames' => [
                ['from' => '/notfound.txt', 'to' => 'found.txt'],
            ],
        ]);

        // THEN
        $response->assertStatus(422);
    });

    it('rejects bulk rename when destination conflicts with non-batch file', function (): void {
        // GIVEN
        Storage::fake('local');
        Storage::disk('local')->put('test/src.txt', 'src');
        Storage::disk('local')->put('test/existing.txt', 'exists');

        // WHEN
        $response = $this->putJson(route('storage-manager.api.fm.bulk-rename'), [
            'disk'    => 'local',
            'renames' => [
                ['from' => '/test/src.txt', 'to' => 'existing.txt'],
            ],
        ]);

        // THEN
        $response->assertStatus(422);
    });

    it('rejects bulk rename when target extension is forbidden', function (): void {
        // GIVEN
        Storage::fake('local');
        Storage::disk('local')->put('items/safe.txt', 'text content');

        // WHEN
        $response = $this->putJson(route('storage-manager.api.fm.bulk-rename'), [
            'disk'    => 'local',
            'renames' => [
                ['from' => '/items/safe.txt', 'to' => 'evil.php'],
            ],
        ]);

        // THEN
        $response->assertStatus(422);
        expect(Storage::disk('local')->exists('items/safe.txt'))->toBeTrue();
        expect(Storage::disk('local')->exists('items/evil.php'))->toBeFalse();
    });

    it('rejects bulk rename when target extension conflicts with file MIME type', function (): void {
        // GIVEN
        Storage::fake('local');
        Storage::disk('local')->put('items/safe.txt', 'plain text content');

        // WHEN
        $response = $this->putJson(route('storage-manager.api.fm.bulk-rename'), [
            'disk'    => 'local',
            'renames' => [
                ['from' => '/items/safe.txt', 'to' => 'FakeImage.jpg'],
            ],
        ]);

        // THEN
        $response->assertStatus(422);
        expect(Storage::disk('local')->exists('items/safe.txt'))->toBeTrue();
        expect(Storage::disk('local')->exists('items/FakeImage.jpg'))->toBeFalse();
    });
});
