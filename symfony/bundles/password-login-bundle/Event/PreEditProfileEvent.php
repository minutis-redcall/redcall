<?php

namespace Bundles\PasswordLoginBundle\Event;

use Bundles\PasswordLoginBundle\Entity\User;

class PreEditProfileEvent extends AbstractEvent
{
    /**
     * @var User
     */
    protected $newUser;

    /**
     * PreEditProfileEvent constructor.
     *
     * @param User $user
     * @param User $newUser
     */
    public function __construct(User $user, User $newUser)
    {
        parent::__construct($user);

        $this->newUser = $newUser;
    }

    /**
     * @return User
     */
    public function getNewUser()
    {
        return $this->newUser;
    }
}
