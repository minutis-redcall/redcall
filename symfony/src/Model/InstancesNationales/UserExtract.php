<?php

namespace App\Model\InstancesNationales;

class UserExtract
{
    const NIVOL_PREFIX = 'user-annu-';

    private $email;

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    public function getNivol()
    {
        // create a slug from the email
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->email)));

        return self::NIVOL_PREFIX.$slug;
    }
}