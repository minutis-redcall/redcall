<?php

namespace App\Entity;

use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @ORM\HasLifecycleCallbacks
 */
class User extends AbstractUser
{

}