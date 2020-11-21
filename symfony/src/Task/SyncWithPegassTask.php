<?php

namespace App\Task;

use App\Manager\RefreshManager;

class SyncWithPegassTask extends AbstractTask
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
        $this->refreshManager->refresh(true);
    }
}