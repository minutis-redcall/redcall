<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends BaseRepository
{
    /**
     * TagRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * @return array
     */
    public function findAll()
    {
        $tags = [];
        foreach ($this->findBy([], ['label' => 'ASC']) as $tag) {
            $tags[$tag->getLabel()] = $tag;
        }

        return $tags;
    }
}
