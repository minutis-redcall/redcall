<?php

namespace Bundles\PasswordLoginBundle\Manager;

use Bundles\PasswordLoginBundle\Base\BaseService;
use Bundles\PasswordLoginBundle\Entity\User;

class UserManager extends BaseService
{
    /**
     * @return User[]
     */
    public function findAll(): array
    {
        return $this->getManager(User::class)->findAll();
    }

    public function findByUsername(string $email): ?User
    {
        return $this->getManager(User::class)->findOneByUsername($email);
    }

    public function save(User $user)
    {
        $this->getManager(User::class)->save($user);
    }
}