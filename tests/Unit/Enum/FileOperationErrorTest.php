<?php

declare(strict_types=1);

namespace Tests\Unit\Enum;

use Illuminate\Support\Facades\App;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;

describe('FileOperationError', function (): void {
    it('translates error messages to English when locale is en', function (): void {
        // GIVEN
        App::setLocale('en');

        // WHEN
        $messageExists        = FileOperationError::DIRECTORY_ALREADY_EXISTS->message();
        $messageNotFound      = FileOperationError::FILE_NOT_FOUND->message();
        $messageForbiddenMime = FileOperationError::FORBIDDEN_FILE_MIME->message();

        // THEN
        expect($messageExists)->toBe('The directory already exists.');
        expect($messageNotFound)->toBe('The file was not found.');
        expect($messageForbiddenMime)->toBe('The file content does not match its extension or is forbidden.');
    });

    it('translates error messages to French when locale is fr', function (): void {
        // GIVEN
        App::setLocale('fr');

        // WHEN
        $messageExists        = FileOperationError::DIRECTORY_ALREADY_EXISTS->message();
        $messageNotFound      = FileOperationError::FILE_NOT_FOUND->message();
        $messageForbiddenMime = FileOperationError::FORBIDDEN_FILE_MIME->message();

        // THEN
        expect($messageExists)->toBe('Le dossier existe déjà.');
        expect($messageNotFound)->toBe('Le fichier est introuvable.');
        expect($messageForbiddenMime)->toBe('Le contenu du fichier ne correspond pas à son extension ou est interdit.');
    });
})->covers(FileOperationError::class);
