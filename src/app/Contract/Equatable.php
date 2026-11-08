<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Contract;

interface Equatable
{
    public function equals(mixed $other): bool;
}
