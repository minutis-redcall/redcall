<?php

namespace App\Manager;

use App\Entity\Pegass;
use App\Repository\PegassRepository;
use App\Services\PegassClient;

class PegassManager
{
    /**
     * @var PegassRepository
     */
    private $pegassRepository;

    /**
     * @var Pegass
     */
    private $pegassClient;

    /**
     * @param PegassRepository $pegassRepository
     * @param PegassClient     $pegassClient
     */
    public function __construct(PegassRepository $pegassRepository, PegassClient $pegassClient)
    {
        $this->pegassRepository = $pegassRepository;
        $this->pegassClient     = $pegassClient;
    }

    /**
     * @param int $limit
     *
     * @throws \Exception
     */
    public function heat(int $limit)
    {
        $this->initialize();

        $entities = $this->pegassRepository->findExpiredEntities($limit);
        foreach ($entities as $entity) {
            /* @var Pegass $entity */
            echo sprintf('Processing %s/%s', $entity->getType(), $entity->getIdentifier()), PHP_EOL;

            switch ($entity->getType()) {
                case Pegass::TYPE_AREA:
                    $this->updateArea();
                    break;
                case Pegass::TYPE_DEPARTMENT:
                    $this->updateDepartment($entity);
                    break;
                case Pegass::TYPE_STRUCTURE:
                    $this->updateStructure($entity);
                    break;
                case Pegass::TYPE_VOLUNTEER:
                    $this->updateVolunteer($entity);
                    break;
            }
        }

        if (!$entities) {
            $this->spreadUpdateDatesInTTL();
        }
    }

    /**
     * @throws \Exception
     */
    private function initialize()
    {
        // Add a sleep of 1 sec between every Pegass API calls
        $this->pegassClient->setMode(PegassClient::MODE_SLOW);

        // Create the first entity if it does not exist
        $area = $this->pegassRepository->getEntity(Pegass::TYPE_AREA);
        if (null === $area) {
            $area = new Pegass();
            $area->setType(Pegass::TYPE_AREA);
            $area->setUpdatedAt(new \DateTime('1984-07-10')); // Expired
            $this->pegassRepository->save($area);
        }
    }

    /**
     * @throws \Exception
     */
    private function updateArea()
    {
        $data = $this->pegassClient->getArea();

        $entity = $this->pegassRepository->getEntity(Pegass::TYPE_AREA);
        $entity->setContent(json_encode($data, JSON_PRETTY_PRINT));
        $this->pegassRepository->save($entity);

        if ($identifiers = array_column($data, 'id')) {
            $this->pegassRepository->removeMissingEntities(Pegass::TYPE_DEPARTMENT, $identifiers);
        }

        foreach ($data as $row) {
            $department = $this->pegassRepository->getEntity(Pegass::TYPE_DEPARTMENT, $row['id']);
            if (null === $department) {
                $department = new Pegass();
                $department->setType(Pegass::TYPE_DEPARTMENT);
                $department->setIdentifier($row['id']);
                $department->setParentIdentifier($row['id']);
                $department->setUpdatedAt(new \DateTime('1984-07-10')); // Expired
                $this->pegassRepository->save($department);
            }
        }
    }

    /**
     * @param Pegass $department
     *
     * @throws \Exception
     */
    private function updateDepartment(Pegass $entity)
    {
        $data = $this->pegassClient->getDepartment($entity->getIdentifier());

        $entity->setContent(json_encode($data, JSON_PRETTY_PRINT));
        $this->pegassRepository->save($entity);

        if (!isset($data['structuresFilles'])) {
            return;
        }

        $identifiers = array_column($data['structuresFilles'], 'id');
        if ($identifiers) {
            $this->pegassRepository->removeMissingEntities(Pegass::TYPE_STRUCTURE, $identifiers, $entity->getParentIdentifier());
        }

        foreach ($data['structuresFilles'] as $row) {
            $structure = $this->pegassRepository->getEntity(Pegass::TYPE_STRUCTURE, $row['id']);
            if (null === $structure) {
                $structure = new Pegass();
                $structure->setType(Pegass::TYPE_STRUCTURE);
                $structure->setIdentifier($row['id']);
                $structure->setParentIdentifier($entity->getIdentifier());
                $structure->setUpdatedAt(new \DateTime('1984-07-10')); // Expired
                $this->pegassRepository->save($structure);
            }
        }
    }

    /**
     * @param Pegass $entity
     *
     * @throws \Exception
     */
    private function updateStructure(Pegass $entity)
    {
        $pages = $this->pegassClient->getStructure($entity->getIdentifier());

        $entity->setContent(json_encode($pages, JSON_PRETTY_PRINT));
        $this->pegassRepository->save($entity);

        $identifiers = [];
        foreach ($pages as $page) {
            $identifiers = array_merge($identifiers, array_column($page['list'], 'id'));
        }
        if ($identifiers) {
            $this->pegassRepository->removeMissingEntities(Pegass::TYPE_VOLUNTEER, $identifiers, $entity->getParentIdentifier());
        }

        foreach ($pages as $page) {
            if (!isset($page['list'])) {
                continue;
            }

            foreach ($page['list'] as $row) {
                $volunteer = $this->pegassRepository->getEntity(Pegass::TYPE_VOLUNTEER, $row['id']);
                if (null === $volunteer) {
                    $volunteer = new Pegass();
                    $volunteer->setType(Pegass::TYPE_VOLUNTEER);
                    $volunteer->setIdentifier($row['id']);
                    $volunteer->setParentIdentifier($entity->getIdentifier());
                    $volunteer->setUpdatedAt(new \DateTime('1984-07-10')); // Expired
                    $this->pegassRepository->save($volunteer);
                }
            }
        }
    }

    /**
     * @param Pegass $entity
     */
    private function updateVolunteer(Pegass $entity)
    {
        $data = $this->pegassClient->getVolunteer($entity->getIdentifier());

        $entity->setContent(json_encode($data, JSON_PRETTY_PRINT));
        $this->pegassRepository->save($entity);
    }

    /**
     * If that's the first time resources are fully loaded, we will update
     * all resource update dates in order to spread out their refreshing
     * on the whole timeframe of their type.
     *
     * Example, if I have 48 volunteers having a TTL of 24h, the first one will
     * be immediately refreshed, the second one 30 mins later, the third one 1h
     * later, etc.
     */
    private function spreadUpdateDatesInTTL()
    {
        $area = $this->pegassRepository->getEntity(Pegass::TYPE_AREA);
        if (!$area || $area->getIdentifier()) {
            return;
        }

        $area->setIdentifier(date('d/m/Y H:i:s'));
        $this->pegassRepository->save($area);

        foreach (Pegass::TTL as $type => $ttl) {
            $entities = $this->pegassRepository->getEntities($type);
            if (!$entities) {
                continue;
            }

            $date = new \DateTime();
            $step = intval($ttl / count($entities));
            foreach ($entities as $entity) {
                $entity->setUpdatedAt($date)->lockUpdateDate();
                $date->add(new \DateInterval(sprintf('PT%dS', $step)));
                $this->pegassRepository->save($entity);
            }
        }
    }
}