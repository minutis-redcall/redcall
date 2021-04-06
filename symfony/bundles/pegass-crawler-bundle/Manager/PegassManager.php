<?php

namespace Bundles\PegassCrawlerBundle\Manager;

use Bundles\PegassCrawlerBundle\Entity\Pegass;
use Bundles\PegassCrawlerBundle\Event\PegassEvent;
use Bundles\PegassCrawlerBundle\PegassEvents;
use Bundles\PegassCrawlerBundle\Repository\PegassRepository;
use Bundles\PegassCrawlerBundle\Service\PegassClient;
use DateInterval;
use DateTime;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Psr\Log\LoggerInterface;
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
     * @var PegassClient
     */
    private $pegassClient;

    /**
     * @var LoggerInterface
     */
    private $slackLogger;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(EventDispatcherInterface $eventDispatcher,
        PegassRepository $pegassRepository,
        PegassClient $pegassClient,
        LoggerInterface $slackLogger,
        LoggerInterface $logger)
    {
        $this->eventDispatcher  = $eventDispatcher;
        $this->pegassRepository = $pegassRepository;
        $this->pegassClient     = $pegassClient;
        $this->slackLogger      = $slackLogger;
        $this->logger           = $logger;
    }

    public function getAllEnabledEntities() : array
    {
        return $this->pegassRepository->getAllEnabledEntities();
    }

    public function getEnabledEntitiesQueryBuilder(?string $type, ?string $identifier) : QueryBuilder
    {
        return $this->pegassRepository->getEnabledEntitiesQueryBuilder($type, $identifier);
    }

    /**
     * @param int  $limit
     * @param bool $fromCache
     *
     * @throws Exception
     */
    public function heat(int $limit, bool $fromCache = false)
    {
        $entity = null;
        try {
            $this->initialize();

            $entities = $this->pegassRepository->findExpiredEntities($limit);

            foreach ($entities as $entity) {
                $this->debug($entity, 'Entity has expired');
                $this->updateEntity($entity, $fromCache);
            }

            if (!$entities) {
                $this->spreadUpdateDatesInTTL();
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to update a Pegass entity', [
                'exception' => $e->getMessage(),
                'entity'    => strval($entity),
            ]);
        }
    }

    /**
     * @param Pegass $entity
     * @param bool   $fromCache
     *
     * @throws Exception
     */
    public function updateEntity(Pegass $entity, bool $fromCache)
    {
        // Just in case entity would not be managed anymore
        $entity = $this->pegassRepository->find($entity->getId());

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

        $this->dispatchEvent($entity);
    }

    /**
     * @param string   $type
     * @param callable $callback
     * @param bool     $onlyEnabled
     *
     * @throws MappingException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function foreach(string $type, callable $callback, bool $onlyEnabled = true)
    {
        $this->pegassRepository->foreach($type, $callback, $onlyEnabled);
    }

    /**
     * @param string $type
     * @param string $identifier
     * @param bool   $onlyEnabled
     *
     * @return Pegass|null
     */
    public function getEntity(string $type, string $identifier, bool $onlyEnabled = true) : ?Pegass
    {
        return $this->pegassRepository->getEntity($type, $identifier, $onlyEnabled);
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
    public function spreadUpdateDatesInTTL()
    {
        $area = $this->pegassRepository->getEntity(Pegass::TYPE_AREA);
        if (!$area || $area->getIdentifier()) {
            return;
        }

        $area->setIdentifier(date('d/m/Y H:i:s'));
        $this->pegassRepository->save($area);

        foreach (Pegass::TTL as $type => $ttl) {
            $count = $this->pegassRepository->countEntities($type);
            $date  = (new DateTime())->sub(new DateInterval(sprintf('PT%dS', $ttl)));
            $step  = intval(($ttl * 24 * 60 * 60) / $count);
            $this->pegassRepository->foreach($type, function (Pegass $entity) use ($date, $step) {
                $updateAt = new DateInterval(sprintf('PT%dS', $step));
                $date->add($updateAt);

                $this->debug($entity, 'Change updatedAt date', [
                    'new-updated-at' => $date->format('d/m/Y H:i:s'),
                ]);

                $entity->setUpdatedAt($date);
                $this->pegassRepository->save($entity);
            });
        }
    }

    /**
     * @throws Exception
     */
    private function initialize()
    {
        // Add a sleep of 1 sec between every Pegass API calls
        $this->pegassClient->setMode(PegassClient::MODE_SLOW);

        // Create the first entity if it does not exist
        $area = $this->pegassRepository->getEntity(Pegass::TYPE_AREA, null, false);
        if (!$area) {
            $area = new Pegass();
            $area->setType(Pegass::TYPE_AREA);
            $area->setUpdatedAt(new DateTime('1984-07-10')); // Expired
            $this->debug($area, 'Creating area');
            $this->pegassRepository->save($area);
        }
    }

    /**
     * @throws Exception
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
        $entity->setUpdatedAt(new DateTime());
        $this->pegassRepository->save($entity);

        if ($identifiers = array_column($data, 'id')) {
            $this->pegassRepository->removeMissingEntities(Pegass::TYPE_DEPARTMENT, $identifiers);
        }

        foreach ($data as $row) {
            $department = $this->pegassRepository->getEntity(Pegass::TYPE_DEPARTMENT, $row['id'], false);
            if (!$department) {
                $department = new Pegass();
                $department->setType(Pegass::TYPE_DEPARTMENT);
                $department->setIdentifier($row['id']);
                $department->setParentIdentifier($row['id']);
                $department->setUpdatedAt(new DateTime('1984-07-10')); // Expired
                $this->debug($department, 'Creating department');
                $this->pegassRepository->save($department);

                $this->slackLogger->info(sprintf('New department created: %s', $department->getIdentifier()));
            }
        }
    }

    /**
     * @param Pegass $entity
     * @param bool   $fromCache
     *
     * @throws Exception
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
        $entity->setUpdatedAt(new DateTime());

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
            if (!$structure) {
                $structure = new Pegass();
                $structure->setType(Pegass::TYPE_STRUCTURE);
                $structure->setIdentifier($row['id']);
                $structure->setParentIdentifier($entity->getIdentifier());
                $structure->setUpdatedAt(new DateTime('1984-07-10')); // Expired
                $this->debug($structure, 'Creating structure');
                $this->pegassRepository->save($structure);

                $this->slackLogger->warning(sprintf(
                    'New structure created in %s/%s: %s (%s)',
                    $data['id'],
                    $data['nom'],
                    $row['libelle'],
                    $structure->getIdentifier()
                ));
            }
        }
    }

    /**
     * @param Pegass $entity
     * @param bool   $fromCache
     *
     * @throws Exception
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
        $entity->setUpdatedAt(new DateTime());
        $entity->setEnabled(true);
        $this->pegassRepository->save($entity);

        $parentIdentifier = sprintf('|%s|', $entity->getIdentifier());

        $identifiers = [];
        foreach ($pages as $page) {
            $list        = $page['list'] ?? $page['content'] ?? [];
            $identifiers = array_merge($identifiers, array_column($list, 'id'));
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
            $list = $page['list'] ?? $page['content'] ?? [];
            foreach ($list as $row) {
                $volunteer = $this->pegassRepository->getEntity(Pegass::TYPE_VOLUNTEER, $row['id'], false);
                if (!$volunteer) {
                    $volunteer = new Pegass();
                    $volunteer->setType(Pegass::TYPE_VOLUNTEER);
                    $volunteer->setIdentifier($row['id']);
                    $volunteer->setParentIdentifier($parentIdentifier);
                    $volunteer->setUpdatedAt(new DateTime('1984-07-10')); // Expired
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
     * @throws Exception
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
        $entity->setUpdatedAt(new DateTime());

        $this->pegassRepository->save($entity);
    }

    /**
     * @param Pegass $entity
     */
    private function debug(Pegass $entity, string $message, array $data = [])
    {
        $params = array_merge($data, [
            'at'                => date('d/m/Y H:i'),
            'type'              => $entity->getType(),
            'identifier'        => $entity->getIdentifier(),
            'parent_identifier' => $entity->getParentIdentifier(),
        ]);

        $this->logger->debug($message, $params);

        echo sprintf('%s %s (%s)', date('d/m/Y H:i:s'), $message, json_encode($params)), PHP_EOL;
    }

    /**
     * @param Pegass $entity
     */
    private function dispatchEvent(Pegass $entity)
    {
        switch ($entity->getType()) {
            case Pegass::TYPE_AREA:
                $this->eventDispatcher->dispatch(new PegassEvent($entity), PegassEvents::UPDATE_AREA);
                break;
            case Pegass::TYPE_DEPARTMENT:
                $this->eventDispatcher->dispatch(new PegassEvent($entity), PegassEvents::UPDATE_DEPARTMENT);
                break;
            case Pegass::TYPE_STRUCTURE:
                $this->eventDispatcher->dispatch(new PegassEvent($entity), PegassEvents::UPDATE_STRUCTURE);
                break;
            case Pegass::TYPE_VOLUNTEER:
                $this->eventDispatcher->dispatch(new PegassEvent($entity), PegassEvents::UPDATE_VOLUNTEER);
                break;
        }
    }
}