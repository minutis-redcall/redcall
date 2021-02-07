<?php

namespace Bundles\PasswordLoginBundle\Event;

use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractEvent extends Event
{
    /**
     * @var AbstractUser
     */
    protected $user;

    /**
     * @param AbstractUser $user
     */
    public function __construct(AbstractUser $user)
    {
        $this->user = $user;
    }

    /**
     * @param AbstractUser $user
     *
     * @return AbstractUser
     */
    public function getUser()
    {
        return $this->user;
    }
}