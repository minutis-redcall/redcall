<?php

namespace App\Task;

use App\Manager\RefreshManager;
use App\Queues;
use Bundles\GoogleTaskBundle\Contracts\TaskInterface;

class SyncWithPegassTask implements TaskInterface
{
    /**
     * @var RefreshManager
     */
    private $refreshManager;

    public function __construct(RefreshManager $refreshManager)
    {
        $this->refreshManager = $refreshManager;
    }

    public function execute(array $context)
    {
        $this->refreshManager->refreshAsync();
    }

    public function getQueueName() : string
    {
        return Queues::SYNC_WITH_PEGASS_ALL;
    }
}