<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Event\BulkRenamed;
use Kwaadpepper\LaravelStorageManager\Event\FileRenamed;

describe('BulkRename', function () {
    it('can rename multiple files in batch', function () {
        Event::fake([BulkRenamed::class, FileRenamed::class]);
        Storage::fake('local');
        Storage::disk('local')->put('photos/img1.jpg', 'content 1');
        Storage::disk('local')->put('photos/img2.jpg', 'content 2');

        $response = $this->putJson(route('storage-manager.api.fm.bulk-rename'), [
            'disk'    => 'local',
            'renames' => [
                ['from' => '/photos/img1.jpg', 'to' => 'photo_01.jpg'],
                ['from' => '/photos/img2.jpg', 'to' => 'photo_02.jpg'],
            ],
        ]);

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

    it('handles cyclic / shifting renames safely', function () {
        Storage::fake('local');
        Storage::disk('local')->put('items/a.txt', 'AAA');
        Storage::disk('local')->put('items/b.txt', 'BBB');

        $response = $this->putJson(route('storage-manager.api.fm.bulk-rename'), [
            'disk'    => 'local',
            'renames' => [
                ['from' => '/items/a.txt', 'to' => 'b.txt'],
                ['from' => '/items/b.txt', 'to' => 'c.txt'],
            ],
        ]);

        $response->assertStatus(200);
        expect(Storage::disk('local')->exists('items/b.txt'))->toBeTrue();
        expect(Storage::disk('local')->get('items/b.txt'))->toBe('AAA');
        expect(Storage::disk('local')->exists('items/c.txt'))->toBeTrue();
        expect(Storage::disk('local')->get('items/c.txt'))->toBe('BBB');
        expect(Storage::disk('local')->exists('items/a.txt'))->toBeFalse();
    });

    it('rejects bulk rename when source file does not exist', function () {
        Storage::fake('local');

        $response = $this->putJson(route('storage-manager.api.fm.bulk-rename'), [
            'disk'    => 'local',
            'renames' => [
                ['from' => '/notfound.txt', 'to' => 'found.txt'],
            ],
        ]);

        $response->assertStatus(422);
    });

    it('rejects bulk rename when destination conflicts with non-batch file', function () {
        Storage::fake('local');
        Storage::disk('local')->put('test/src.txt', 'src');
        Storage::disk('local')->put('test/existing.txt', 'exists');

        $response = $this->putJson(route('storage-manager.api.fm.bulk-rename'), [
            'disk'    => 'local',
            'renames' => [
                ['from' => '/test/src.txt', 'to' => 'existing.txt'],
            ],
        ]);

        $response->assertStatus(422);
    });
});
