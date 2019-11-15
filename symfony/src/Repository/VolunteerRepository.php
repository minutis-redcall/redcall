<?php

namespace App\Repository;

use App\Entity\Tag;
use App\Entity\Volunteer;
use App\Entity\VolunteerImport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
     * @param array $nivolsToDisable
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function disableByNivols(array $nivolsToDisable)
    {
        foreach ($nivolsToDisable as $nivolToDisable) {
            /* @var \App\Entity\Volunteer $volunteer */
            $volunteer = $this->findOneByNivol($nivolToDisable);

            if ($volunteer && !$volunteer->isLocked() && $volunteer->isEnabled()) {
                $volunteer->setReport([]);
                $volunteer->addError('Volunteer is not in the organization anymore.');
                $volunteer->setEnabled(false);
            }

            $this->_em->persist($volunteer);
        }

        $this->_em->flush();
    }

    /**
     * @param Volunteer $import
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function import(Volunteer $import)
    {
        $volunteer = $this->findOneByNivol($import->getNivol());
        if (!$volunteer) {
            $volunteer = $import;
        } else if ($volunteer->isLocked()) {
            $volunteer->setReport([]);
            $volunteer->addWarning('Cannot update a locked volunteer.');
            $this->save($volunteer);

            return;
        } else {
            $volunteer->setFirstName($import->getFirstName());
            $volunteer->setLastName($import->getLastName());
            if ($import->getPhoneNumber()) {
                $volunteer->setPhoneNumber($import->getPhoneNumber());
            }
            if ($import->getEmail()) {
                $volunteer->setEmail($import->getEmail());
            }
            $volunteer->setMinor($import->isMinor());
            $volunteer->setReport([]);
            $volunteer->setEnabled(true);
        }

        if (!$volunteer->getPhoneNumber() && !$volunteer->getEmail()) {
            $volunteer->addError('Volunteer has no phone and no email.');
            $volunteer->setEnabled(false);
        } else if (!$volunteer->getPhoneNumber()) {
            $volunteer->addWarning('Volunteer has no phone number.');
        } else if (!$volunteer->getEmail()) {
            $volunteer->addWarning('Volunteer has no email.');
        }

        if ($volunteer->isMinor()) {
            $volunteer->addError('Volunteer is minor.');
            $volunteer->setEnabled(false);
        }

        if (!$volunteer->getLastPegassUpdate()) {
            $volunteer->setLastPegassUpdate(new \DateTime('2000-01-01'));
        }

        $this->save($volunteer);
    }

    /**
     * @param int $limit
     *
     * @return array
     *
     * @throws \Exception
     */
    public function findVolunteersToRefresh(int $limit) : array
    {
        return $this
            ->createQueryBuilder('v')
            ->andWhere('v.enabled = true')
            ->andWhere('v.locked = false')
            ->andWhere('v.lastPegassUpdate < :lastMonth')
            ->setParameter('lastMonth', new \DateTime('last month'))
            ->orderBy('v.lastPegassUpdate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Volunteer $volunteer
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Volunteer $volunteer)
    {
        $this->_em->persist($volunteer);
        $this->_em->flush($volunteer);
    }

    /**
     * @param Volunteer $volunteer
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove(Volunteer $volunteer)
    {
        $this->_em->remove($volunteer);
        $this->_em->flush($volunteer);
    }
}
