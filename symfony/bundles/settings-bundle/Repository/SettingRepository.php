<?php

namespace Bundles\SettingsBundle\Repository;

use Bundles\SettingsBundle\Entity\Setting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bridge\Doctrine\RegistryInterface;

class SettingRepository extends ServiceEntityRepository
{
    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    /**
     * @return array
     */
    public function all(): array
    {
        $settings = [];
        $entities = $this->findAll();

        foreach ($entities as $entity) {
            $settings[$entity->getProperty()] = $entity->getValue();
        }

        return $entity;
    }

    /**
     * @param string      $property
     * @param string|null $default
     *
     * @return string|null
     */
    public function get(string $property, ?string $default = null): ?string
    {
        $entity = $this->findOneByProperty($property);

        if ($entity) {
            return $entity->getValue();
        }

        return $default;
    }

    /**
     * @param string $property
     * @param string $value
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function set(string $property, string $value)
    {
        $entity = $this->findOneByProperty($property);

        if (!$entity) {
            $entity = new Setting();
            $entity->setProperty($property);
        }

        $entity->setValue($value);

        $this->_em->persist($entity);
        $this->_em->flush($entity);
    }

    /**
     * @param string $property
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(string $property)
    {
        $entity = $this->findOneByProperty($property);

        if ($entity) {
            $this->_em->remove($entity);
            $this->_em->flush($entity);
        }
    }
}