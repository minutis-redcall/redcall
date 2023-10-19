<?php

namespace App\Model\InstancesNationales;

class UsersExtract
{
    /**
     * @var UserExtract[]
     */
    private $users = [];

    /**
     * @return UsersExtract[]
     */
    public function getUsers() : array
    {
        return $this->users;
    }

    public function addUser(UserExtract $user) : void
    {
        $this->users[] = $user;
    }

    public function count() : int
    {
        return count($this->users);
    }
}