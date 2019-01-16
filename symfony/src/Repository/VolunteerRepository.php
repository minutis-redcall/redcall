<?php

namespace App\Repository;

use App\Entity\Tag;
use App\Entity\Volunteer;
use App\Entity\VolunteerImport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Volunteer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Volunteer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Volunteer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VolunteerRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Volunteer::class);
    }

    public function findAllEnabledVolunteers()
    {
        return $this->findBy(['enabled' => true], ['firstName' => 'ASC']);
    }

    /**
     * @param array $volunteerIds
     *
     * @return Volunteer[]
     */
    public function findByIds(array $volunteerIds)
    {
        return $this
            ->createQueryBuilder('v')
            ->andWhere('v.id IN (:ids)')
            ->setParameter('ids', $volunteerIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array
     */
    public function getVolunteersCountByTags(): array
    {
        $rows = $this->_em->createQueryBuilder('t')
                          ->select('t.id, COUNT(v.id) AS c')
                          ->from(Tag::class, 't')
                          ->join('t.volunteers', 'v')
                          ->where('v.enabled = true')
                          ->groupBy('t.id')
                          ->getQuery()
                          ->getArrayResult();

        $tagCounts = [];
        foreach ($rows as $row) {
            $tagCounts[$row['id']] = $row['c'];
        }

        return $tagCounts;
    }

    /**
     * Receives a sanitized volunteer coming from the google
     * spreadsheet containing volunteers, and insert or
     * update it accordingly.
     *
     * @param array           $tags
     * @param VolunteerImport $import
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function import(array $tags, VolunteerImport $import): void
    {
        $volunteer = $this->findOneByNivol($import->getNivol());

        if ($volunteer && $volunteer->isLocked()) {
            $import->addError('Volunteer is locked');
            $this->_em->persist($import);
            $this->_em->flush($import);

            return;
        }

        if (!$import->isImportable()) {
            if ($volunteer) {
                $volunteer->setEnabled(false);
                $this->_em->persist($volunteer);
                $this->_em->flush($volunteer);
            }

            return;
        }

        if (!$volunteer) {
            $volunteer = new Volunteer();
        }

        $volunteer->setNivol($import->getNivol());
        $volunteer->setFirstName($import->getFirstName());
        $volunteer->setLastName($import->getLastName());
        $volunteer->setPhoneNumber($import->getPhone());
        $volunteer->setPostalCode($import->getPostalCode());
        $volunteer->setEmail($import->getEmail());
        $volunteer->setEnabled(true);

        $volunteer->getTags()->clear();
        foreach ($import->getTags() as $tagLabel => $isEnabled) {
            if ($isEnabled && isset($tags[$tagLabel])) {
                $volunteer->getTags()->add($tags[$tagLabel]);
            }
        }

        // Cannot flush at the end of the batch because of potential
        // duplicates on unique keys
        $this->_em->persist($volunteer);
        $this->_em->flush($volunteer);
    }

    /**
     * This method is called at the end of an import.
     *
     * It compares the import table with the volunteers table in case a few
     * volunteers have been removed from the google spreadhseet. They should
     * be disabled in that case.
     */
    public function disableNonImportedVolunteers()
    {
        $miss = $this->createQueryBuilder('v')
                     ->leftJoin(VolunteerImport::class, 'i', Join::WITH, 'v.nivol = i.nivol')
                     ->where('v.enabled = true')
                     ->andWhere('v.locked = false')
                     ->andWhere('i.id IS NULL')
                     ->getQuery()
                     ->getResult();

        foreach ($miss as $volunteer) {
            $volunteer->setEnabled(false);
            $this->_em->persist($volunteer);
        }

        $this->_em->flush();
    }
}
