<?php

namespace App\Task;

use App\Entity\Pegass;
use App\Manager\DeletedVolunteerManager;
use App\Manager\PegassManager;
use App\Manager\RefreshManager;
use App\Manager\StructureManager;
use App\Manager\VolunteerManager;
use App\Queues;
use Bundles\GoogleTaskBundle\Contracts\TaskInterface;
use Twig\Environment;

class PegassUpdateChunk implements TaskInterface
{
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
     * @var DeletedVolunteerManager
     */
    private $deletedVolunteerManager;

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(PegassManager $pegassManager,
        StructureManager $structureManager,
        VolunteerManager $volunteerManager,
        RefreshManager $refreshManager,
        DeletedVolunteerManager $deletedVolunteerManager,
        Environment $twig)
    {
        $this->pegassManager           = $pegassManager;
        $this->structureManager        = $structureManager;
        $this->volunteerManager        = $volunteerManager;
        $this->refreshManager          = $refreshManager;
        $this->deletedVolunteerManager = $deletedVolunteerManager;
        $this->twig                    = $twig;
    }

    public function execute(array $context)
    {
        foreach ($context['chunk'] as $identifier => $data) {
            switch ($context['type']) {
                case Pegass::TYPE_STRUCTURE:
                    $this->updateStructure($identifier, $data);
                    break;
                case Pegass::TYPE_VOLUNTEER:
                    $this->updateVolunteer($identifier, $data);
                    break;
                case SyncOneWithPegass::SYNC_STRUCTURES:
                    $this->structureManager->synchronizeWithPegass();
                    break;
                case SyncOneWithPegass::SYNC_VOLUNTEERS:
                    $this->volunteerManager->synchronizeWithPegass();
                    break;
                case SyncOneWithPegass::PARENT_STRUCUTRES:
                    $this->refreshManager->refreshParentStructures();
                    break;
            }
        }
    }

    public function getQueueName() : string
    {
        return Queues::PEGASS_UPDATE_CHUNK;
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
    }

    private function updateVolunteer(string $identifier, array $data)
    {
        if (!$entity = $this->pegassManager->getEntity(Pegass::TYPE_VOLUNTEER, $identifier, false)) {
            if ($this->deletedVolunteerManager->isDeleted($identifier)) {
                // Prevents deleted volunteers through GDPR form to be reimported
                return;
            }

            $parentIdentifier = '|'.($data['structure_id'] ? sprintf('%s|', $data['structure_id']) : '');
            $entity           = $this->pegassManager->createNewEntity(Pegass::TYPE_VOLUNTEER, $identifier, $parentIdentifier);
        }

        $json = $this->twig->render('pegass/volunteer.json.twig', [
            'volunteer' => $data,
        ]);

        if ($decoded = json_decode($json, true)) {
            $this->pegassManager->updateEntity($entity, $decoded);
        }
    }
}