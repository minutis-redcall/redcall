<?php

namespace App\Manager;

use App\Entity\Structure;
use App\Entity\Volunteer;
use App\Repository\StructureRepository;

class StructureManager
{
    /**
     * @var StructureRepository
     */
    private $structureRepository;

    /**
     * @param StructureRepository $structureRepository
     */
    public function __construct(StructureRepository $structureRepository)
    {
        $this->structureRepository = $structureRepository;
    }

    /**
     * @param int $id
     *
     * @return Structure|null
     */
    public function find(int $id): ?Structure
    {
        return $this->structureRepository->find($id);
    }

    /**
     * @return array
     */
    public function listStructureIdentifiers(): array
    {
        return $this->structureRepository->listStructureIdentifiers();
    }

    /**
     * @param string $identifier
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function disableByIdentifier(string $identifier)
    {
        $this->structureRepository->disableByIdentifier($identifier);
    }

    /**
     * @param string $identifier
     *
     * @return Structure|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByIdentifier(string $identifier): ?Structure
    {
        return $this->structureRepository->findOneByIdentifier($identifier);
    }

    /**
     * @param Structure $structure
     */
    public function save(Structure $structure)
    {
        $this->structureRepository->save($structure);
    }

    /**
     * @param Volunteer $volunteer
     *
     * @return array
     */
    public function findCallableStructuresForVolunteer(Volunteer $volunteer): array
    {
        return $this->structureRepository->findCallableStructuresForVolunteer($volunteer);
    }

}