<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Contract;

interface Executor
{
    /**
     * @template TInputAction of ActionInput
     * @template TInputQuery of QueryInput
     * @template TResult
     *
     * @param  Action<TInputAction, TResult>|Query<TInputQuery, TResult>  $executable
     * @param  TInputAction|TInputQuery  $input
     * @return TResult
     */
    public function execute(Action | Query $executable, ActionInput | QueryInput $input): mixed;
}
