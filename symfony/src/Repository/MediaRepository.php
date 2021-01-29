<?php

namespace App\Repository;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(array $criteria, array $orderBy = null)
 * @method Media[]    findAll()
 * @method Media[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    public function save(Media $media)
    {
        $this->_em->persist($media);
        $this->_em->flush();
    }

    public function clearExpired()
    {
        $this->createQueryBuilder('m')
             ->delete(Media::class, 'm')
             ->where('m.expiresAt < :now')
             ->setParameter('now', new \DateTime())
             ->getQuery()
             ->execute();
    }
}
