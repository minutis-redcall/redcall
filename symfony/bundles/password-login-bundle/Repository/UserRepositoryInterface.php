<?php

namespace Bundles\PasswordLoginBundle\Repository;

use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Doctrine\Persistence\ObjectRepository;

interface UserRepositoryInterface extends ObjectRepository
{
    public function findOneByUsername(string $email): ?AbstractUser;

    public function searchAll(?string $criteria): array;

    public function save(AbstractUser $user);

    public function remove(AbstractUser $user);
}