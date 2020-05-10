<?php

namespace App\Repository;

use App\Entity\User;
use Bundles\PasswordLoginBundle\Repository\AbstractUserRepository;
use Bundles\PasswordLoginBundle\Repository\UserRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends AbstractUserRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
}
