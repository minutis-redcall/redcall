<?php

namespace Bundles\PasswordLoginBundle\Event;

use Bundles\PasswordLoginBundle\Entity\AbstractUser;

class PostEditProfileEvent extends AbstractEvent
{
    /**
     * @var AbstractUser
     */
    protected $oldUser;

    /**
     * PostEditProfileEvent constructor.
     *
     * @param AbstractUser $user
     * @param AbstractUser $oldUser
     */
    public function __construct(AbstractUser $user, AbstractUser $oldUser)
    {
        parent::__construct($user);

        $this->oldUser = $oldUser;
    }

    /**
     * @return AbstractUser
     */
    public function getOldUser()
    {
        return $this->oldUser;
    }
}
