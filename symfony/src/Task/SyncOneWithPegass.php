<?php

namespace App\Task;

use App\Manager\RefreshManager;
use App\Manager\StructureManager;
use App\Manager\VolunteerManager;
use App\Queues;
use Bundles\GoogleTaskBundle\Contracts\TaskInterface;
use App\Entity\Pegass;
use App\Manager\PegassManager;

class SyncOneWithPegass implements TaskInterface
{
    const PARENT_STRUCUTRES = 'parent_structures';
    const SYNC_STRUCTURES   = 'sync_structures';
    const SYNC_VOLUNTEERS   = 'sync_volunteers';

    /**
     * @var RefreshManager
     */
    private $refreshManager;

    /**
     * @var PegassManager
     */
    private $pegassManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    public function __construct(RefreshManager $refreshManager,
        PegassManager $pegassManager,
        StructureManager $structureManager,
        VolunteerManager $volunteerManager)
    {
        $this->refreshManager   = $refreshManager;
        $this->pegassManager    = $pegassManager;
        $this->structureManager = $structureManager;
        $this->volunteerManager = $volunteerManager;
    }

    public function execute(array $context)
    {
        switch ($context['type']) {
            case Pegass::TYPE_STRUCTURE:
                $pegass = $this->pegassManager->getEntity($context['type'], $context['identifier']);
                $this->refreshManager->refreshStructure($pegass, true);
                break;
            case self::SYNC_STRUCTURES:
                $this->structureManager->synchronizeWithPegass();
                break;
            case self::SYNC_VOLUNTEERS:
                $this->volunteerManager->synchronizeWithPegass();
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
