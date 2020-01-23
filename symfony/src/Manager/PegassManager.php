<?php

namespace App\Manager;

use App\Entity\Pegass;
use App\Event\PegassEvent;
use App\Repository\PegassRepository;
use App\Services\PegassClient;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PegassManager
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var PegassRepository
     */
    private $pegassRepository;

    /**
     * @var Pegass
     */
    private $pegassClient;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param PegassRepository         $pegassRepository
     * @param PegassClient             $pegassClient
     */
    public function __construct(EventDispatcherInterface $eventDispatcher,
        PegassRepository $pegassRepository,
        PegassClient $pegassClient)
    {
        $this->eventDispatcher  = $eventDispatcher;
        $this->pegassRepository = $pegassRepository;
        $this->pegassClient     = $pegassClient;
        $this->output           = new NullOutput();
    }

    /**
     * @param int  $limit
     * @param bool $fromCache
     *
     * @throws \Exception
     */
    public function heat(int $limit, OutputInterface $output, bool $fromCache = false)
    {
        $this->output = $output;

        $this->initialize();

        $entities = $this->pegassRepository->findExpiredEntities($limit);

        foreach ($entities as $entity) {
            $this->debug($entity, 'Entity has expired');
            $this->updateEntity($entity, $fromCache);
        }

        if (!$entities) {
            $this->spreadUpdateDatesInTTL();
        }
    }

    /**
     * @param Pegass $entity
     * @param bool   $fromCache
     *
     * @throws \Exception
     */
    public function updateEntity(Pegass $entity, bool $fromCache)
    {
        switch ($entity->getType()) {
            case Pegass::TYPE_AREA:
                $this->updateArea($entity, $fromCache);
                break;
            case Pegass::TYPE_DEPARTMENT:
                $this->updateDepartment($entity, $fromCache);
                break;
            case Pegass::TYPE_STRUCTURE:
                $this->updateStructure($entity, $fromCache);
                break;
            case Pegass::TYPE_VOLUNTEER:
                $this->updateVolunteer($entity, $fromCache);
                break;
        }

        $this->eventDispatcher->dispatch(new PegassEvent($entity));
    }

    /**
     * @param string $type
     *
     * @return array
     */
    public function listIdentifiers(string $type): array
    {
        return $this->pegassRepository->listIdentifiers($type);
    }

    /**
     * @param string   $type
     * @param callable $callback
     * @param bool     $onlyEnabled
     *
     * @return int
     */
    public function foreach(string $type, callable $callback, bool $onlyEnabled = true): int
    {
        return $this->pegassRepository->foreach($type, $callback, $onlyEnabled);
    }

    /**
     * @param string      $type
     * @param string      $identifier
     * @param string|null $parentIdentifier
     *
     * @return Pegass|null
     */
    public function getEntity(string $type, string $identifier): ?Pegass
    {
        return $this->pegassRepository->getEntity($type, $identifier);
    }

    /**
     * @throws \Exception
     */
    private function initialize()
    {
        // Add a sleep of 1 sec between every Pegass API calls
        $this->pegassClient->setMode(PegassClient::MODE_SLOW);

        // Create the first entity if it does not exist
        $area = $this->pegassRepository->getEntity(Pegass::TYPE_AREA, null, false);
        if (null === $area) {
            $area = new Pegass();
            $area->setType(Pegass::TYPE_AREA);
            $area->setUpdatedAt(new \DateTime('1984-07-10')); // Expired
            $this->debug($area, 'Creating area');
            $this->pegassRepository->save($area);
        }
    }

    /**
     * @throws \Exception
     */
    private function updateArea(Pegass $entity, bool $fromCache)
    {
        if (!$fromCache) {
            $data = $this->pegassClient->getArea();
        } else {
            if (!$data = $entity->getContent()) {
                return;
            }
        }

        $entity->setContent($data);
        $entity->setUpdatedAt(new \DateTime());
        $this->pegassRepository->save($entity);

        if ($identifiers = array_column($data, 'id')) {
            $this->pegassRepository->removeMissingEntities(Pegass::TYPE_DEPARTMENT, $identifiers);
        }

        foreach ($data as $row) {
            $department = $this->pegassRepository->getEntity(Pegass::TYPE_DEPARTMENT, $row['id'], false);
            if (null === $department) {
                $department = new Pegass();
                $department->setType(Pegass::TYPE_DEPARTMENT);
                $department->setIdentifier($row['id']);
                $department->setParentIdentifier($row['id']);
                $department->setUpdatedAt(new \DateTime('1984-07-10')); // Expired
                $this->debug($department, 'Creating department');
                $this->pegassRepository->save($department);
            }
        }
    }

    /**
     * @param Pegass $entity
     * @param bool   $fromCache
     *
     * @throws \Exception
     */
    private function updateDepartment(Pegass $entity, bool $fromCache)
    {
        if (!$fromCache) {
            $data = $this->pegassClient->getDepartment($entity->getIdentifier());
        } else {
            if (!$data = $entity->getContent()) {
                return;
            }
        }

        $entity->setContent($data);
        $entity->setUpdatedAt(new \DateTime());
        $this->pegassRepository->save($entity);

        if (!isset($data['structuresFilles'])) {
            return;
        }

        $identifiers = array_column($data['structuresFilles'], 'id');
        if ($identifiers) {
            $this->pegassRepository->removeMissingEntities(Pegass::TYPE_STRUCTURE, $identifiers, $entity->getParentIdentifier());
        }

        foreach ($data['structuresFilles'] as $row) {
            $structure = $this->pegassRepository->getEntity(Pegass::TYPE_STRUCTURE, $row['id'], false);
            if (null === $structure) {
                $structure = new Pegass();
                $structure->setType(Pegass::TYPE_STRUCTURE);
                $structure->setIdentifier($row['id']);
                $structure->setParentIdentifier($entity->getIdentifier());
                $structure->setUpdatedAt(new \DateTime('1984-07-10')); // Expired
                $this->debug($structure, 'Creating structure');
                $this->pegassRepository->save($structure);
            }
        }
    }

    /**
     * @param Pegass $entity
     * @param bool   $fromCache
     *
     * @throws \Exception
     */
    private function updateStructure(Pegass $entity, bool $fromCache)
    {
        if (!$fromCache) {
            $structure = $this->pegassClient->getStructure($entity->getIdentifier());
        } else {
            if (!$structure = $entity->getContent()) {
                return;
            }
        }

        $pages = $structure['volunteers'];

        $entity->setContent($structure);
        $entity->setUpdatedAt(new \DateTime());
        $this->pegassRepository->save($entity);

        $parentIdentifier = sprintf('|%s|', $entity->getIdentifier());

        $identifiers = [];
        foreach ($pages as $page) {
            $identifiers = array_merge($identifiers, array_column($page['list'], 'id'));
        }
        if ($identifiers) {
            $missingEntities = $this->pegassRepository->findMissingEntities(Pegass::TYPE_VOLUNTEER, $identifiers, $parentIdentifier);
            foreach ($missingEntities as $missingEntity) {
                $missingEntity->setParentIdentifier(str_replace($parentIdentifier, '|', $missingEntity->getParentIdentifier()));
                if ('|' === $missingEntity->getParentIdentifier()) {
                    $missingEntity->setParentIdentifier(null);
                    $missingEntity->setEnabled(false);
                }
                $this->pegassRepository->save($entity);
            }
        }

        foreach ($pages as $page) {
            if (!isset($page['list'])) {
                continue;
            }

            foreach ($page['list'] as $row) {
                $volunteer = $this->pegassRepository->getEntity(Pegass::TYPE_VOLUNTEER, $row['id'], $entity->getIdentifier(), false);
                if (null === $volunteer) {
                    $volunteer = new Pegass();
                    $volunteer->setType(Pegass::TYPE_VOLUNTEER);
                    $volunteer->setIdentifier($row['id']);
                    $volunteer->setParentIdentifier($parentIdentifier);
                    $volunteer->setUpdatedAt(new \DateTime('1984-07-10')); // Expired
                    $this->debug($volunteer, 'Creating volunteer');
                    $this->pegassRepository->save($volunteer);
                } else {
                    if (false === strpos($volunteer->getParentIdentifier(), $parentIdentifier)) {
                        $volunteer->setParentIdentifier(
                            sprintf('%s%s|', $volunteer->getParentIdentifier(), $entity->getIdentifier())
                        );
                    }
                }
            }
        }
    }

    /**
     * @param Pegass $entity
     * @param bool   $fromCache
     *
     * @throws \Exception
     */
    private function updateVolunteer(Pegass $entity, bool $fromCache)
    {
        if (!$fromCache) {
            $data = $this->pegassClient->getVolunteer($entity->getIdentifier());
        } else {
            if (!$data = $entity->getContent()) {
                return;
            }
        }

        $entity->setContent($data);
        $entity->setUpdatedAt(new \DateTime());

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
        $area = $this->pegassRepository->getEntity(Pegass::TYPE_AREA, null);
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
                $updateAt = new \DateInterval(sprintf('PT%dS', $step));
                $date->add($updateAt);

                $this->debug($entity, 'updated at becomes', $date->format('d/m/Y H:i:s'));

                $entity->setUpdatedAt($date);
                $this->pegassRepository->save($entity);
            }
        }
    }

    /**
     * @param Pegass $entity
     * @param string ...$data
     */
    private function debug(Pegass $entity, string ...$data)
    {
        $prefix = sprintf(
            '%s: %s/%s|%s(%d):',
            date('d/m/Y H:i'),
            $entity->getType(),
            $entity->getIdentifier(),
            ltrim($entity->getParentIdentifier(), '|'),
            $entity->getId()
        );

        $this->output->writeln(implode(' ', array_merge([$prefix], $data)), OutputInterface::VERBOSITY_VERBOSE);
    }
}