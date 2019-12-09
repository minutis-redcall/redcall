<?php

namespace App\Base;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class BaseRepository extends ServiceEntityRepository
{
    public function save($entity)
    {
        $this->_em->persist($entity);
        $this->_em->flush($entity);
    }

    public function remove($entity)
    {
        $this->_em->remove($entity);
        $this->_em->flush($entity);
    }
}