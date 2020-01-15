<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Structure;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Structure|null find($id, $lockMode = null, $lockVersion = null)
 * @method Structure|null findOneBy(array $criteria, array $orderBy = null)
 * @method Structure[]    findAll()
 * @method Structure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StructureRepository extends BaseRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Structure::class);
    }

    /**
     * @return array
     */
    public function listStructureIdentifiers(): array
    {
        $rows = $this->createQueryBuilder('s')
                     ->select('s.identifier')
                     ->getQuery()
                     ->getArrayResult();

        return array_column($rows, 'identifier');
    }

    /**
     * @param string $identifier
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function disableByIdentifier(string $identifier)
    {
        $this->createQueryBuilder('s')
             ->update()
             ->set('s.enabled = :enabled')
             ->setParameter('enabled', false)
             ->where('s.identifier = :identifier')
             ->setParameter('identifier', $identifier)
             ->getQuery()
             ->execute();
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
        return $this->createQueryBuilder('s')
                    ->where('s.identifier = :identifier')
                    ->setParameter('identifier', $identifier)
                    ->getQuery()
                    ->getOneOrNullResult();
    }

}
