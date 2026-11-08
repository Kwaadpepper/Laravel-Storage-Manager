<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controller;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Event\FileCopying;
use Kwaadpepper\LaravelStorageManager\Event\FileCreating;
use Kwaadpepper\LaravelStorageManager\Event\FileDeleting;
use Kwaadpepper\LaravelStorageManager\Event\FileMoving;
use Kwaadpepper\LaravelStorageManager\Event\FileRenaming;
use Kwaadpepper\LaravelStorageManager\Http\Controller\BasicOperationsController;

describe('BeforeEventsCancellationApi', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
        Config::set('storage-manager.trash.enabled', false);
        Config::set('storage-manager.upload.allowed_extensions', ['txt', 'pdf']);
        \App::forgetInstance(StorageManagerConfig::class);
    });

    it('returns 422 domain error when FileCreating is cancelled via event listener', function (): void {
        // GIVEN
        Event::listen(FileCreating::class, function (FileCreating $event): void {
            $event->cancel('Quota utilisateur dépassé (50 Mo max)');
        });

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.create-file'), [
            'disk'    => 'local',
            'path'    => '/',
            'name'    => 'quota_file.txt',
            'content' => 'hello world',
        ]);

        // THEN
        $response->assertStatus(422)
            ->assertJson([
                'status' => 422,
                'errors' => [
                    'code'    => 1021,
                    'message' => 'Quota utilisateur dépassé (50 Mo max)',
                ],
            ]);
        expect(Storage::disk('local')->exists('quota_file.txt'))->toBeFalse();
    });

    it('returns 422 domain error when FileDeleting is cancelled via event listener', function (): void {
        // GIVEN
        Storage::disk('local')->put('contract.txt', 'signed agreement');
        Event::listen(FileDeleting::class, function (FileDeleting $event): void {
            $event->cancel('Fichier verrouillé par le contrat #42');
        });

        // WHEN
        $response = $this->deleteJson(route('storage-manager.api.fm.delete'), [
            'disk'  => 'local',
            'path'  => '/contract.txt',
            'force' => true,
        ]);

        // THEN
        $response->assertStatus(422)
            ->assertJson([
                'status' => 422,
                'errors' => [
                    'code'    => 1021,
                    'message' => 'Fichier verrouillé par le contrat #42',
                ],
            ]);
        expect(Storage::disk('local')->exists('contract.txt'))->toBeTrue();
    });

    it('returns 422 domain error when FileRenaming is cancelled via event listener', function (): void {
        // GIVEN
        Storage::disk('local')->put('old_name.txt', 'data');
        Event::listen(FileRenaming::class, function (FileRenaming $event): void {
            $event->cancel('Renommage interdit sur ce fichier');
        });

        // WHEN
        $response = $this->putJson(route('storage-manager.api.fm.rename'), [
            'disk' => 'local',
            'path' => '/old_name.txt',
            'to'   => 'new_name.txt',
        ]);

        // THEN
        $response->assertStatus(422)
            ->assertJson([
                'status' => 422,
                'errors' => [
                    'code'    => 1021,
                    'message' => 'Renommage interdit sur ce fichier',
                ],
            ]);
        expect(Storage::disk('local')->exists('old_name.txt'))->toBeTrue();
        expect(Storage::disk('local')->exists('new_name.txt'))->toBeFalse();
    });

    it('returns 422 domain error when FileCopying is cancelled via event listener', function (): void {
        // GIVEN
        Storage::disk('local')->put('source.txt', 'data to copy');
        Storage::disk('local')->makeDirectory('target');
        Event::listen(FileCopying::class, function (FileCopying $event): void {
            $event->cancel('Copie non autorisée');
        });

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.copy'), [
            'disk'            => 'local',
            'path'            => '/source.txt',
            'destination_dir' => '/target',
        ]);

        // THEN
        $response->assertStatus(422)
            ->assertJson([
                'status' => 422,
                'errors' => [
                    'code'    => 1021,
                    'message' => 'Copie non autorisée',
                ],
            ]);
        expect(Storage::disk('local')->exists('target/source.txt'))->toBeFalse();
    });

    it('returns 422 domain error when FileMoving is cancelled via event listener', function (): void {
        // GIVEN
        Storage::disk('local')->put('source_move.txt', 'data to move');
        Storage::disk('local')->makeDirectory('target');
        Event::listen(FileMoving::class, function (FileMoving $event): void {
            $event->cancel('Déplacement interdit');
        });

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.move'), [
            'disk'            => 'local',
            'path'            => '/source_move.txt',
            'destination_dir' => '/target',
        ]);

        // THEN
        $response->assertStatus(422)
            ->assertJson([
                'status' => 422,
                'errors' => [
                    'code'    => 1021,
                    'message' => 'Déplacement interdit',
                ],
            ]);
        expect(Storage::disk('local')->exists('source_move.txt'))->toBeTrue();
        expect(Storage::disk('local')->exists('target/source_move.txt'))->toBeFalse();
    });
})->covers(BasicOperationsController::class);
