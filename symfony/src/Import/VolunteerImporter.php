<?php

namespace App\Import;

use App\Entity\Volunteer;
use App\Manager\StructureManager;
use App\Manager\TagManager;
use App\Manager\VolunteerManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class VolunteerImporter extends Importer
{
    /** @var StructureManager */
    private $structureManager;

    /** @var VolunteerManager */
    private $volunteerManager;

    /** @var TagManager */
    private $tagManager;

    /**
     * VolunteerImporter constructor.
     *
     * @param Connection             $connection
     * @param ValidatorInterface     $validator
     * @param LoggerInterface        $logger
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface    $translator
     * @param StructureManager       $structureManager
     * @param VolunteerManager       $volunteerManager
     * @param TagManager             $tagManager
     */
    public function __construct(
        Connection $connection,
        ValidatorInterface $validator,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        StructureManager $structureManager,
        VolunteerManager $volunteerManager,
        TagManager $tagManager
    )
    {
        parent::__construct($connection, $validator, $logger, $entityManager, $translator);

        $this->structureManager = $structureManager;
        $this->volunteerManager = $volunteerManager;
        $this->tagManager = $tagManager;
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
            'structures' => explode(',', $data[4]),
            'identifier' => $data[5],
            'tags' => explode(',', $data[6]),
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
    protected function importLine(array &$data): bool
    {
        $volunteer = $this->volunteerManager->findOneByIdentifier($data['identifier']);
        if (!is_null($volunteer)) {
            return false;
        }

        $volunteer = new Volunteer();
        $volunteer
            ->setFirstName($data['first_name'])
            ->setLastName($data['last_name'])
            ->setEmail($data['email'])
            ->setPhoneNumber($data['phone_number'])
            ->setIdentifier($data['identifier'])
            ->setNivol($data['identifier'])
            ->setReport([])
        ;

        // Get the structures.
        foreach ($data['structures'] as $name) {
            $structure = $this->structureManager->findOneByName($name);
            if (is_null($structure)) {
                throw new \RuntimeException("Structure $name was not found");
            }

            $volunteer->addStructure($structure);
        }

        // Get the tags.
        foreach ($data['tags'] as $label) {
            $tag = $this->tagManager->findOneByLabel($label);
            if (is_null($tag)) {
                throw new \RuntimeException("Tag $label was not found");
            }

            $volunteer->addTag($tag);
        }

        $this->entityManager->persist($volunteer);
        $this->entityManager->flush($volunteer);

        return true;
    }
}
