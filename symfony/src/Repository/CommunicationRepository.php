<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Communication;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Communication|null find($id, $lockMode = null, $lockVersion = null)
 * @method Communication|null findOneBy(array $criteria, array $orderBy = null)
 * @method Communication[]    findAll()
 * @method Communication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommunicationRepository extends BaseRepository
{
    /**
     * CommunicationRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, Communication::class);
    }

    /**
     * @param Communication $communication
     * @param string        $newName
     */
    public function changeName(Communication $communication, string $newName) : void
    {
        $communication->setLabel($newName);
        $this->save($communication);
    }
}
