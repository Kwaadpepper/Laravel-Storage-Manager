<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Contract;

/**
 * @template TInput of QueryInput
 * @template TResult
 */
interface Query
{
    public function ability(): string;

    /**
     * @param  TInput  $input
     * @return TResult
     */
    public function __invoke(QueryInput $input);
}
