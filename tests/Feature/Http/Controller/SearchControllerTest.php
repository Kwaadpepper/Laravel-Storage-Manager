<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;

describe('SearchController', function () {

    it('can search files and filter by name, extension, and size', function () {
        Storage::fake('local');
        Storage::disk('local')->put('testdir/test1.txt', 'Hello World');
        Storage::disk('local')->put('testdir/test2.pdf', 'PDF Content');
        Storage::disk('local')->put('testdir/large.txt', str_repeat('A', 5000));
        Storage::disk('local')->makeDirectory('testdir/subdir');

        $response = $this->postJson(route('storage-manager.api.fm.search'), [
            'disk'  => 'local',
            'paths' => ['/testdir'],
            'query' => 'test',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'directoriesToScan',
                    'matchedDirectories',
                    'matchedFiles',
                ],
            ]);

        $files = $response->json('data.matchedFiles');
        expect(count($files))->toBe(2);

        $extensions = array_map(fn ($f) => $f['extension'], $files);
        expect($extensions)->toContain('txt');
        expect($extensions)->toContain('pdf');

        $directories = $response->json('data.directoriesToScan');
        expect(count($directories))->toBe(1);
        expect($directories[0])->toBe('/testdir/subdir');

        $responseExt = $this->postJson(route('storage-manager.api.fm.search'), [
            'disk'      => 'local',
            'paths'     => ['/testdir'],
            'extension' => 'pdf',
        ]);
        $responseExt->assertStatus(200);
        expect(count($responseExt->json('data.matchedFiles')))->toBe(1);
        expect($responseExt->json('data.matchedFiles')[0]['path'])->toBe('/testdir/test2.pdf');

        $responseSize = $this->postJson(route('storage-manager.api.fm.search'), [
            'disk'    => 'local',
            'paths'   => ['/testdir'],
            'minSize' => 1000,
        ]);
        $responseSize->assertStatus(200);
        expect(count($responseSize->json('data.matchedFiles')))->toBe(1);
        expect($responseSize->json('data.matchedFiles')[0]['path'])->toBe('/testdir/large.txt');
    });

});
