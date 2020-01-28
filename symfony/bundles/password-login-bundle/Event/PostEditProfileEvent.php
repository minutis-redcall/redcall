<?php

namespace Bundles\PasswordLoginBundle\Event;

use Bundles\PasswordLoginBundle\Entity\User;

class PostEditProfileEvent extends AbstractEvent
{
    /**
     * @var User
     */
    protected $oldUser;

    /**
     * PostEditProfileEvent constructor.
     *
     * @param User $user
     * @param User $oldUser
     */
    public function __construct(User $user, User $oldUser)
    {
        parent::__construct($user);

        $this->oldUser = $oldUser;
    }

    /**
     * @return User
     */
    public function getOldUser()
    {
        return $this->oldUser;
    }
}
