<?php

namespace Bundles\SandboxBundle\Base;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class BaseRepository extends ServiceEntityRepository
{
    public function truncate()
    {
        $this->createQueryBuilder('t')->delete()->getQuery()->execute();
    }

    public function save($entity)
    {
        $this->_em->persist($entity);
        $this->_em->flush();
    }

    public function remove($entity)
    {
        $this->_em->remove($entity);
        $this->_em->flush();
    }
}