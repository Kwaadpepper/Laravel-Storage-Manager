<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Http\Request\ApiRequest;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathSanitizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

class BulkRenameRequest extends ApiRequest
{
    private PathSanitizer $pathSanitizer;

    public function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->pathSanitizer = resolve(PathSanitizer::class);

        /** @var array<int, array<string, mixed>>|null $renames */
        $renames = $this->input('renames');

        if (! is_array($renames)) {
            return;
        }

        $sanitized = [];
        foreach ($renames as $item) {
            if (! is_array($item)) {
                continue;
            }
            $to = isset($item['to']) && is_string($item['to'])
                ? $this->pathSanitizer->sanitizeFileName($item['to'])
                : '';
            $from = isset($item['from']) && is_string($item['from'])
                ? $item['from']
                : '';

            $sanitized[] = [
                'from' => $from,
                'to'   => $to,
            ];
        }

        $this->merge([
            'renames' => $sanitized,
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'renames'        => ['required', 'array', 'min:1'],
            'renames.*.from' => ['required', 'string', 'min:1', 'starts_with:/'],
            'renames.*.to'   => ['required', 'string', 'min:1', 'max:255'],
        ];
    }

    /**
     * @return array<int, array{from: Path, to: string}>
     */
    public function getRenames(): array
    {
        /** @var array<int, array{from: string, to: string}> $renames */
        $renames = $this->input('renames', []);

        return array_map(
            fn (array $item): array => [
                'from' => new Path($item['from']),
                'to'   => $item['to'],
            ],
            $renames
        );
    }
}
