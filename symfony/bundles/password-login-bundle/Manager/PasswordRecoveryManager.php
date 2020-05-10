<?php

namespace Bundles\PasswordLoginBundle\Manager;

use Bundles\PasswordLoginBundle\Entity\PasswordRecovery;
use Bundles\PasswordLoginBundle\Repository\PasswordRecoveryRepository;

class PasswordRecoveryManager
{
    /**
     * @var PasswordRecoveryRepository
     */
    private $passwordRecoveryRepository;

    /**
     * @param PasswordRecoveryRepository $passwordRecoveryRepository
     */
    public function __construct(PasswordRecoveryRepository $passwordRecoveryRepository)
    {
        $this->passwordRecoveryRepository = $passwordRecoveryRepository;
    }

    public function find(string $username): ?PasswordRecovery
    {
        return $this->passwordRecoveryRepository->find($username);
    }

    public function clearExpired()
    {
        $this->passwordRecoveryRepository->clearExpired();
    }

    public function generateToken(string $username): string
    {
        return $this->passwordRecoveryRepository->generateToken($username);
    }

    public function getByToken(string $token): ?PasswordRecovery
    {
        return $this->passwordRecoveryRepository->getByToken($token);
    }

    public function remove(PasswordRecovery $passwordRecovery)
    {
        $this->passwordRecoveryRepository->remove($passwordRecovery);
    }
}