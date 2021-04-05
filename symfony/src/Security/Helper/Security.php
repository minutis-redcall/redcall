<?php

namespace App\Security\Helper;

use App\Entity\User;
use Symfony\Component\Security\Core\Security as BaseSecurity;

class Security extends BaseSecurity
{
    public function getPlatform() : ?string
    {
        if (!$this->getUser()) {
            return null;
        }

        if (!($this->getUser() instanceof User)) {
            return null;
        }

        return $this->getUser()->getPlatform();
    }
}