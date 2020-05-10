<?php

namespace Bundles\PasswordLoginBundle\Event;

use Bundles\PasswordLoginBundle\Entity\AbstractUser;

class PreEditProfileEvent extends AbstractEvent
{
    /**
     * @var AbstractUser
     */
    protected $newUser;

    /**
     * PreEditProfileEvent constructor.
     *
     * @param AbstractUser $user
     * @param AbstractUser $newUser
     */
    public function __construct(AbstractUser $user, AbstractUser $newUser)
    {
        parent::__construct($user);

        $this->newUser = $newUser;
    }

    /**
     * @return AbstractUser
     */
    public function getNewUser()
    {
        return $this->newUser;
    }
}
