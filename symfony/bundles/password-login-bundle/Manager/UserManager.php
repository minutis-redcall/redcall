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
}