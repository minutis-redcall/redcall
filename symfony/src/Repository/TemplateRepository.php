<?php

namespace App\Repository;

use App\Entity\Structure;
use App\Entity\Template;
use App\Entity\User;
use App\Enum\Type;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Template|null find($id, $lockMode = null, $lockVersion = null)
 * @method Template|null findOneBy(array $criteria, array $orderBy = null)
 * @method Template[]    findAll()
 * @method Template[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Template::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Template $entity, bool $flush = true) : void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Template $entity, bool $flush = true) : void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function getTemplatesForStructure(Structure $structure) : QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')
                   ->where('t.structure = :structure')
                   ->setParameter('structure', $structure)
                   ->orderBy('t.priority', 'DESC');

        return $qb;
    }

    /**
     * @param User $user
     *
     * @return Template[]
     */
    public function findByTypeForUserStructures(User $user, Type $type) : array
    {
        return $this->createQueryBuilder('t')
                    ->where('t.type = :type')
                    ->setParameter('type', $type)
                    ->join('t.structure', 's')
                    ->join('s.users', 'u')
                    ->andWhere('u.id = :user')
                    ->setParameter('user', $user)
                    ->orderBy('t.priority', 'DESC')
                    ->getQuery()
                    ->getResult();
    }
}
