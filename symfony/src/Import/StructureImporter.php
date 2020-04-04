<?php

namespace App\Import;

use App\Entity\Structure;
use App\Repository\StructureRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StructureImporter extends Importer
{
    /** @var StructureRepository */
    private $structureRepository;

    /**
     * StructureImporter constructor.
     *
     * @param Connection             $connection
     * @param ValidatorInterface     $validator
     * @param LoggerInterface        $logger
     * @param EntityManagerInterface $entityManager
     * @param StructureRepository    $structureRepository
     */
    public function __construct(
        Connection $connection,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        StructureRepository $structureRepository)
    {
        parent::__construct($connection, $validator, $logger, $entityManager);

        $this->structureRepository = $structureRepository;
    }

    /**
     * {@inheritDoc}
     */
    protected function normalizeLine(array $data): array
    {
        return [
            'identifier' => (int) $data[0],
            'type' => $data[1],
            'name' => $data[2],
            'parent_structure' => !empty($data[3]) ? $data[3] : null,
            'enabled' => (bool) $data[4],
            'president' => $data[5],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function sort()
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function getConstraints(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    protected function importLine(array &$data)
    {
        $structure = new Structure();
        $structure
            ->setIdentifier($data['identifier'])
            ->setType($data['type'])
            ->setName($data['name'])
            ->setEnabled($data['enabled'])
            ->setPresident($data['president'])
        ;

        // Get the parent structure if needed.
        if (!is_null($data['parent_structure'])) {
            $parentStructure = $this->structureRepository->findOneBy([
                'name' => $data['parent_structure'],
            ]);

            if (is_null($parentStructure)) {
                throw new \RuntimeException('Parent structure was not found');
            }

            $structure->setParentStructure($parentStructure);
        }

        $this->entityManager->persist($structure);
        $this->entityManager->flush($structure);
    }
}
