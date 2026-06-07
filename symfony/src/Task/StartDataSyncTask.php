<?php

namespace App\Task;

use App\Queues;
use App\Sync\DataSyncOrchestrator;
use Bundles\GoogleTaskBundle\Contracts\TaskInterface;

class StartDataSyncTask implements TaskInterface
{
    private DataSyncOrchestrator $orchestrator;

    public function __construct(DataSyncOrchestrator $orchestrator)
    {
        $this->orchestrator = $orchestrator;
    }

    public function execute(array $context)
    {
        $syncedAt = isset($context['syncedAt'])
            ? new \DateTimeImmutable($context['syncedAt'])
            : new \DateTimeImmutable();

        $this->orchestrator->start($syncedAt);
    }

    public function getQueueName() : string
    {
        return Queues::SYNC_START;
    }
}
