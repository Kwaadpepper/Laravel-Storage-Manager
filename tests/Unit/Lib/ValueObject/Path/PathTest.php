<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

describe('Path', function (): void {
    it('stores the value as a readonly property', function (): void {
        // GIVEN
        $value = '/uploads/photo.jpg';

        // WHEN
        $path = new Path($value);

        // THEN
        expect($path->value)->toBe($value);
    });

    it('casts to string via __toString', function (): void {
        // GIVEN
        $value = '/uploads/photo.jpg';
        $path  = new Path($value);

        // WHEN
        $result = (string) $path;

        // THEN
        expect($result)->toBe($value);
    });

    it('throws a DomainException when path does not start with a slash', function (): void {
        // GIVEN
        $invalidPath = 'no-leading-slash';

        // WHEN
        $action = fn (): Path => new Path($invalidPath);

        // THEN
        expect($action)
            ->toThrow(IllegalDomainStateException::class, "Path must start with a '/'.");
    });

    it('throws a DomainException when path is empty', function (): void {
        // GIVEN
        $emptyPath = '';

        // WHEN
        $action = fn (): Path => new Path($emptyPath);

        // THEN
        expect($action)
            ->toThrow(IllegalDomainStateException::class, "Path must start with a '/'.");
    });

    it('appends a segment without a leading slash to a base path', function (): void {
        // GIVEN
        $basePath = new Path('/uploads');
        $segment  = 'photo.jpg';

        // WHEN
        $result = Path::appendTo($basePath, $segment);

        // THEN
        expect((string) $result)->toBe('/uploads/photo.jpg');
    });

    it('appends a segment with a leading slash by normalising it', function (): void {
        // GIVEN
        $basePath = new Path('/uploads');
        $segment  = '/photo.jpg';

        // WHEN
        $result = Path::appendTo($basePath, $segment);

        // THEN
        expect((string) $result)->toBe('/uploads/photo.jpg');
    });

    it('strips the trailing slash of the base path before appending', function (): void {
        // GIVEN
        $basePath = new Path('/uploads/');
        $segment  = 'photo.jpg';

        // WHEN
        $result = Path::appendTo($basePath, $segment);

        // THEN
        expect((string) $result)->toBe('/uploads/photo.jpg');
    });

    it('rejects null bytes in path', function (): void {
        // GIVEN
        $invalidPath = "/uploads/\0evil.txt";

        // WHEN
        $action = fn (): Path => new Path($invalidPath);

        // THEN
        expect($action)->toThrow(IllegalDomainStateException::class, 'Path must not contain null bytes.');
    });

    it('rejects path segments containing ..', function (): void {
        // GIVEN
        $invalidPath = '/uploads/../secret.txt';

        // WHEN
        $action = fn (): Path => new Path($invalidPath);

        // THEN
        expect($action)->toThrow(IllegalDomainStateException::class, "Path must not contain '..' segments.");
    });

    it('returns root path via static root() factory', function (): void {
        // GIVEN / WHEN
        $root = Path::root();

        // THEN
        expect($root->value)->toBe('/');
        expect($root->isRoot())->toBeTrue();
    });

    it('recognises non-root paths as not root', function (): void {
        // GIVEN
        $path = new Path('/uploads/file.txt');

        // WHEN / THEN
        expect($path->isRoot())->toBeFalse();
    });

    it('returns Flysystem-compatible relative path via toRelative()', function (): void {
        // GIVEN
        $path = new Path('/uploads/photo.jpg');

        // WHEN
        $rel = $path->toRelative();

        // THEN
        expect($rel)->toBe('uploads/photo.jpg');
    });

    it('returns empty string from toRelative() for root path', function (): void {
        // GIVEN
        $path = Path::root();

        // WHEN / THEN
        expect($path->toRelative())->toBe('');
    });

    it('returns parent directory as a Path', function (): void {
        // GIVEN
        $path = new Path('/uploads/2024/photo.jpg');

        // WHEN
        $parent = $path->parent();

        // THEN
        expect($parent->value)->toBe('/uploads/2024');
    });

    it('returns root when calling parent() on a top-level path', function (): void {
        // GIVEN
        $path = new Path('/uploads');

        // WHEN
        $parent = $path->parent();

        // THEN
        expect($parent->value)->toBe('/');
        expect($parent->isRoot())->toBeTrue();
    });

    it('returns basename of the path', function (): void {
        // GIVEN
        $path = new Path('/uploads/photo.jpg');

        // WHEN / THEN
        expect($path->basename())->toBe('photo.jpg');
    });

    it('returns filename without extension', function (): void {
        // GIVEN
        $path = new Path('/uploads/photo.jpg');

        // WHEN / THEN
        expect($path->filename())->toBe('photo');
    });

    it('returns lowercase extension', function (): void {
        // GIVEN
        $path = new Path('/uploads/photo.JPG');

        // WHEN / THEN
        expect($path->extension())->toBe('jpg');
    });

    it('returns empty extension for files without extension', function (): void {
        // GIVEN
        $path = new Path('/uploads/README');

        // WHEN / THEN
        expect($path->extension())->toBe('');
    });

    it('returns true from equals() when paths are identical', function (): void {
        // GIVEN
        $a = new Path('/uploads/file.txt');
        $b = new Path('/uploads/file.txt');

        // WHEN / THEN
        expect($a->equals($b))->toBeTrue();
    });

    it('returns false from equals() when paths differ', function (): void {
        // GIVEN
        $a = new Path('/uploads/file.txt');
        $b = new Path('/uploads/other.txt');

        // WHEN
        $result = $a->equals($b);

        // THEN
        expect($result)->toBeFalse();
    });

    it('determines if path starts with a prefix correctly', function (): void {
        // GIVEN
        $path       = new Path('/uploads/documents/report.pdf');
        $root       = new Path('/');
        $same       = new Path('/uploads/documents/report.pdf');
        $parent     = new Path('/uploads');
        $nested     = new Path('/uploads/documents');
        $nonPrefix  = new Path('/uploads/doc');
        $different  = new Path('/archive');

        // WHEN
        $matchesRoot      = $path->startsWith($root);
        $matchesSame      = $path->startsWith($same);
        $matchesParent    = $path->startsWith($parent);
        $matchesNested    = $path->startsWith($nested);
        $matchesNonPrefix = $path->startsWith($nonPrefix);
        $matchesDiff      = $path->startsWith($different);

        // THEN
        expect($matchesRoot)->toBeTrue()
            ->and($matchesSame)->toBeTrue()
            ->and($matchesParent)->toBeTrue()
            ->and($matchesNested)->toBeTrue()
            ->and($matchesNonPrefix)->toBeFalse()
            ->and($matchesDiff)->toBeFalse();
    });

    it('computes relative path correctly with relativeTo', function (): void {
        // GIVEN
        $path = new Path('/uploads/documents/report.pdf');
        $base = new Path('/uploads');
        $root = new Path('/');
        $same = new Path('/uploads/documents/report.pdf');

        // WHEN
        $relToBase = $path->relativeTo($base);
        $relToRoot = $path->relativeTo($root);
        $relToSame = $path->relativeTo($same);

        // THEN
        expect($relToBase)->toBe('documents/report.pdf')
            ->and($relToRoot)->toBe('uploads/documents/report.pdf')
            ->and($relToSame)->toBe('');
    });

    it('throws InvalidArgumentException when calling relativeTo on non-parent base path', function (): void {
        // GIVEN
        $path    = new Path('/uploads/documents/report.pdf');
        $outside = new Path('/archive');

        // WHEN
        $action = fn (): string => $path->relativeTo($outside);

        // THEN
        expect($action)->toThrow(\InvalidArgumentException::class, "Path '/uploads/documents/report.pdf' does not start with base path '/archive'.");
    });

    it('rebases a path to a target base directory with rebase', function (): void {
        // GIVEN
        $path       = new Path('/uploads/documents/report.pdf');
        $sourceBase = new Path('/uploads');
        $targetBase = new Path('/archive/2026');

        // WHEN
        $rebased = $path->rebase($sourceBase, $targetBase);

        // THEN
        expect($rebased->value)->toBe('/archive/2026/documents/report.pdf');
    });

    it('returns target base when rebasing identical path', function (): void {
        // GIVEN
        $path       = new Path('/uploads');
        $sourceBase = new Path('/uploads');
        $targetBase = new Path('/archive/backup');

        // WHEN
        $rebased = $path->rebase($sourceBase, $targetBase);

        // THEN
        expect($rebased->value)->toBe('/archive/backup');
    });

    it('rebases from root directory correctly', function (): void {
        // GIVEN
        $path       = new Path('/documents/report.pdf');
        $sourceBase = new Path('/');
        $targetBase = new Path('/backup');

        // WHEN
        $rebased = $path->rebase($sourceBase, $targetBase);

        // THEN
        expect($rebased->value)->toBe('/backup/documents/report.pdf');
    });
});
