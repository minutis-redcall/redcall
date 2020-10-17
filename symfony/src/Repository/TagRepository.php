<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;

/**
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends BaseRepository
{
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function findAll()
    {
        $tags = [];
        foreach ($this->findBy([], ['label' => 'ASC']) as $tag) {
            $tags[$tag->getLabel()] = $tag;
        }

        return $tags;
    }

    public function findAllQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('t')
                    ->orderBy('t.id', 'asc');
    }

    public function findTagsByNivol(array $nivols): array
    {
        return $this->createQueryBuilder('t')
                    ->select('v.nivol, t.label')
                    ->join('t.volunteers', 'v')
                    ->where('v.nivol IN (:nivols)')
                    ->setParameter('nivols', $nivols)
                    ->orderBy('t.id', 'ASC')
                    ->getQuery()
                    ->getArrayResult();
    }
}
