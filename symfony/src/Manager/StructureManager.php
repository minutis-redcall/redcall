<?php

namespace App\Manager;

use App\Entity\Structure;
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
    public function getStructureByIdentifier(string $identifier): ?Structure
    {
        return $this->structureRepository->getStructureByIdentifier($identifier);
    }

    /**
     * @param Structure $structure
     */
    public function save(Structure $structure)
    {
        $this->structureRepository->save($structure);
    }
}