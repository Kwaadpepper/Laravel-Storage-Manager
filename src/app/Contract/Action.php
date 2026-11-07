<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Contract;

/**
 * @template TInput of ActionInput
 * @template TResult
 */
interface Action
{
    public function ability(): string;

    /**
     * @param  TInput  $input
     * @return TResult
     */
    public function __invoke(ActionInput $input);
}
