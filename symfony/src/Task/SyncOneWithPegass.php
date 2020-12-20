<?php

namespace App\Task;

use App\Manager\RefreshManager;
use App\Queues;
use Bundles\GoogleTaskBundle\Api\TaskInterface;
use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Bundles\PegassCrawlerBundle\Manager\PegassManager;

class SyncOneWithPegass implements TaskInterface
{
    const PARENT_STRUCUTRES = 'parent_structures';

    /**
     * @var RefreshManager
     */
    private $refreshManager;

    /**
     * @var PegassManager
     */
    private $pegassManager;

    public function __construct(RefreshManager $refreshManager, PegassManager $pegassManager)
    {
        $this->refreshManager = $refreshManager;
        $this->pegassManager  = $pegassManager;
    }

    public function execute(array $context)
    {
        switch ($context['type']) {
            case Pegass::TYPE_STRUCTURE:
                $pegass = $this->pegassManager->getEntity($context['type'], $context['identifier']);
                $this->refreshManager->refreshStructure($pegass, true);
                break;
            case self::PARENT_STRUCUTRES:
                $this->refreshManager->refreshParentStructures();
                break;
            case Pegass::TYPE_VOLUNTEER:
                $pegass = $this->pegassManager->getEntity($context['type'], $context['identifier']);
                $this->refreshManager->refreshVolunteer($pegass, true);
                break;
        }
    }

    public function getQueueName() : string
    {
        return Queues::SYNC_WITH_PEGASS_ONE;
    }
}
