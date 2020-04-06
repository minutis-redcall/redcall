<?php

namespace App\Import;

use App\Entity\Structure;
use App\Manager\StructureManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class StructureImporter extends Importer
{
    /** @var StructureManager */
    private $structureManager;

    /**
     * StructureImporter constructor.
     *
     * @param Connection             $connection
     * @param ValidatorInterface     $validator
     * @param LoggerInterface        $logger
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface    $translator
     * @param StructureManager       $structureManager
     */
    public function __construct(
        Connection $connection,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        StructureManager $structureManager
    )
    {
        parent::__construct($connection, $validator, $logger, $entityManager, $translator);

        $this->structureManager = $structureManager;
    }

    /**
     * {@inheritDoc}
     */
    protected function normalizeLine(array $data): array
    {
        return [
            'identifier' => (int) $data[0],
            'type' => 'foo',
            'name' => $data[1],
            'parent_structure' => !empty($data[2]) ? $data[2] : null,
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
        return [
            'identifier' => [
                new Type([
                    'type' => 'int'
                ]),
                new GreaterThan([
                    'value' => 0,
                ]),
                new LessThanOrEqual([
                    'value' => 4294967295,
                ]),
            ],
            'type' => new Length([
                'min' => 1,
                'max' => 16,
            ]),
            'name' => new Length([
                'min' => 1,
                'max' => 255,
            ]),
            'parent_structure' => [],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function importLine(array &$data): bool
    {
        // Assess if the structure already exists.
        $structure = $this->structureManager->findOneByIdentifier($data['identifier']);

        if (!is_null($structure)) {
            return false;
        }

        $structure = new Structure();
        $structure
            ->setIdentifier($data['identifier'])
            ->setType($data['type'])
            ->setName($data['name'])
        ;

        // Get the parent structure if needed.
        if (!is_null($data['parent_structure'])) {
            $parentStructure = $this->structureManager->findOneByName($data['parent_structure']);

            if (is_null($parentStructure)) {
                throw new \RuntimeException('Parent structure was not found');
            }

            $structure->setParentStructure($parentStructure);
        }

        $this->entityManager->persist($structure);
        $this->entityManager->flush($structure);

        return true;
    }
}
