<?php

namespace App\Task;

use App\Entity\Pegass;
use App\Manager\PegassManager;
use App\Manager\RefreshManager;
use App\Manager\StructureManager;
use App\Manager\VolunteerManager;
use App\Queues;
use Bundles\GoogleTaskBundle\Contracts\TaskInterface;
use Twig\Environment;

class PegassUpdateOneEntity implements TaskInterface
{
    const PARENT_STRUCUTRES = 'parent_structures';
    const SYNC_STRUCTURES   = 'sync_structures';
    const SYNC_VOLUNTEERS   = 'sync_volunteers';

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

    /**
     * @var RefreshManager
     */
    private $refreshManager;

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(PegassManager $pegassManager,
        StructureManager $structureManager,
        VolunteerManager $volunteerManager,
        RefreshManager $refreshManager,
        Environment $twig)
    {
        $this->pegassManager    = $pegassManager;
        $this->structureManager = $structureManager;
        $this->volunteerManager = $volunteerManager;
        $this->refreshManager   = $refreshManager;
        $this->twig             = $twig;
    }

    public function execute(array $context)
    {
        switch ($context['type']) {
            case Pegass::TYPE_STRUCTURE:
                $this->updateStructure($context['identifier'], $context['data']);
                break;
            case Pegass::TYPE_VOLUNTEER:
                $this->updateVolunteer($context['identifier'], $context['data']);
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
        }
    }

    public function getQueueName() : string
    {
        return Queues::PEGASS_UPDATE_ONE_ENTITY;
    }

    private function updateStructure(string $identifier, array $data)
    {
        if (!$entity = $this->pegassManager->getEntity(Pegass::TYPE_STRUCTURE, $identifier, false)) {
            if (!isset($this->structures[$parentId = $data['parent_id']])) {
                $parentId = $identifier;
            }

            $entity = $this->pegassManager->createNewEntity(Pegass::TYPE_STRUCTURE, $identifier, $parentId);
        }

        $json = $this->twig->render('pegass/structure.json.twig', [
            'structure' => $data,
        ]);

        if ($decoded = json_decode($json, true)) {
            $this->pegassManager->updateEntity($entity, $decoded);
        }

        $this->pegassManager->flush();
    }

    private function updateVolunteer(string $identifier, array $data)
    {
        if (!$entity = $this->pegassManager->getEntity(Pegass::TYPE_VOLUNTEER, $identifier, false)) {
            $parentIdentifier = '|'.($data['structure_id'] ? sprintf('%s|', $data['structure_id']) : '');
            $entity           = $this->pegassManager->createNewEntity(Pegass::TYPE_VOLUNTEER, $identifier, $parentIdentifier);
        }

        $json = $this->twig->render('pegass/volunteer.json.twig', [
            'volunteer' => $data,
        ]);

        if ($decoded = json_decode($json, true)) {
            $this->pegassManager->updateEntity($entity, $decoded);
        }

        $this->pegassManager->flush();
    }
}