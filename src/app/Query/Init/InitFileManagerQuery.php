<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Init;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Contract\Query;
use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Event\FileManagerShowed;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;
use Psr\Log\LoggerInterface;

/**
 * @implements Query<InitFileManagerInput, array{chunkMinSize: int, chunkMaxSize: int}>
 */
final readonly class InitFileManagerQuery implements Query
{
    public function __construct(
        private StorageManagerConfig $config,
        private EventDispatcher $eventDispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::INIT;
    }

    /**
     * @param  InitFileManagerInput  $input
     * @return array{chunkMinSize: int, chunkMaxSize: int}
     */
    public function __invoke(QueryInput $input): array
    {
        $this->logger->info('Initializing file manager');
        $this->eventDispatcher->dispatch(FileManagerShowed::class);

        return [
            'chunkMinSize' => $this->config->upload->chunkMinSize,
            'chunkMaxSize' => $this->config->upload->chunkMaxSize,
        ];
    }
}
