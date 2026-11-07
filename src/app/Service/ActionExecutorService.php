<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

use Illuminate\Contracts\Auth\Access\Gate;
use Kwaadpepper\LaravelStorageManager\ActivityLog\ActivityLogger;
use Kwaadpepper\LaravelStorageManager\Contract\Action;
use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Contract\Executor;
use Kwaadpepper\LaravelStorageManager\Contract\Query;
use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Psr\Log\LoggerInterface;

final readonly class ActionExecutorService implements Executor
{
    public function __construct(
        private Gate $gate,
        private ActivityLogger $activityLogger,
        private AuthService $authService,
        private LoggerInterface $logger,
    ) {
    }

    public function execute(Action | Query $executable, ActionInput | QueryInput $input): mixed
    {
        $executableClass = $executable::class;
        $actionName      = class_basename($executableClass);
        $type            = $executable instanceof Action ? 'Action' : 'Query';
        $user            = $this->authService->user();
        $ability         = $executable->ability();

        if ($executable instanceof Action) {
            $this->assertInputIsAction($input);
        } else {
            $this->assertInputIsQuery($input);
        }

        $disk = $input->getDisk();
        $path = $input->getPath();

        $arguments = match (true) {
            $disk !== null || $path !== null => [$disk, $path],
            default                          => [],
        };

        $this->gate->forUser($user)->authorize($ability, $arguments);

        $this->logger->info("[StorageManager][{$type}] Executing {$actionName}", [
            'action' => $executableClass,
            'input'  => $input::class,
            'user'   => $user?->getAuthIdentifier(),
        ]);

        try {
            $result = $executable($input);

            if ($executable instanceof Action) {
                $this->activityLogger->record($user, $executable, $input, $result);
            }

            $this->logger->info("[StorageManager][{$type}] Succeeded {$actionName}");

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error("[StorageManager][{$type}] Failed {$actionName}: {$e->getMessage()}", [
                'action'    => $executableClass,
                'input'     => $input::class,
                'message'   => $e->getMessage(),
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * @phpstan-assert QueryInput $input
     */
    private function assertInputIsQuery(mixed $input): void
    {
        if (! $input instanceof QueryInput) {
            throw new \InvalidArgumentException('Input must be an instance of QueryInput.');
        }
    }

    /**
     * @phpstan-assert ActionInput $input
     */
    private function assertInputIsAction(mixed $input): void
    {
        if (! $input instanceof ActionInput) {
            throw new \InvalidArgumentException('Input must be an instance of ActionInput.');
        }
    }
}
