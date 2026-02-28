<?php

namespace App\Tests\Fixtures;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures
{
    private $entityManager;
    private $encoder;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $encoder)
    {
        $this->entityManager = $entityManager;
        $this->encoder = $encoder;
    }

    public function createRawUser(string $username = 'user', string $password = 'password', bool $admin = false, bool $verified = true): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setPlatform('fr');
        $user->setLocale('fr');
        $user->setTimezone('Europe/Paris');
        $user->setPassword($this->encoder->encodePassword($user, $password));
        $user->setIsVerified($verified);
        $user->setIsTrusted(true);
        $user->setIsRoot($admin);
        $user->setIsAdmin($admin);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function createVolunteer(
        User $user,
        string $externalId = '123456789',
        string $email = 'volunteer@example.com'
    ): \App\Entity\Volunteer {
        $volunteer = new \App\Entity\Volunteer();
        $volunteer->setExternalId($externalId);
        $volunteer->setPlatform($user->getPlatform());
        $volunteer->setEmail($email);
        $volunteer->setUser($user);
        $volunteer->setEnabled(true);
        $volunteer->setLocked(false);
        $volunteer->setPhoneNumberOptin(true);
        $volunteer->setEmailOptin(true);

        $user->setVolunteer($volunteer);

        $this->entityManager->persist($volunteer);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $volunteer;
    }
}
