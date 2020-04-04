<?php

namespace App\Import;

use App\Entity\Volunteer;
use App\Repository\StructureRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VolunteerImporter extends Importer
{
    /** @var StructureRepository */
    private $structureRepository;

    /**
     * VolunteerImporter constructor.
     *
     * @param Connection             $connection
     * @param ValidatorInterface     $validator
     * @param LoggerInterface        $logger
     * @param EntityManagerInterface $entityManager
     * @param StructureRepository    $structureRepository
     s*/
    public function __construct(
        Connection $connection,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        StructureRepository $structureRepository
    )
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
            'first_name' => $data[0],
            'last_name' => $data[1],
            'email' => $data[2],
            'phone_number' => $data[3],
            'structure' => $data[4],
            'identifier' => $data[5],
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
        $volunteer = new Volunteer();
        $volunteer
            ->setFirstName($data['first_name'])
            ->setLastName($data['last_name'])
            ->setEmail($data['email'])
            ->setPhoneNumber($data['phone_number'])
            ->setNivol($data['identifier'])
            ->setReport([])
        ;

        // Get the structure.
        $structure = $this->structureRepository->findOneBy([
            'name' => $data['structure'],
        ]);

        if (is_null($structure)) {
            throw new \RuntimeException('Structure was not found');
        }

        $volunteer->addStructure($structure);
        $this->entityManager->persist($volunteer);
        $this->entityManager->flush($volunteer);
    }
}
