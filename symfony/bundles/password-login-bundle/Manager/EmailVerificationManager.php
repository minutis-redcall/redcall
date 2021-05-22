<?php

namespace Bundles\PasswordLoginBundle\Manager;

use Bundles\PasswordLoginBundle\Entity\EmailVerification;
use Bundles\PasswordLoginBundle\Repository\EmailVerificationRepository;

class EmailVerificationManager
{
    /**
     * @var EmailVerificationRepository
     */
    private $emailVerificationRepository;

    /**
     * @param EmailVerificationRepository $emailVerificationRepository
     */
    public function __construct(EmailVerificationRepository $emailVerificationRepository)
    {
        $this->emailVerificationRepository = $emailVerificationRepository;
    }

    public function find(string $username) : ?EmailVerification
    {
        return $this->emailVerificationRepository->find($username);
    }

    public function getExpiredUsernames() : array
    {
        return $this->emailVerificationRepository->getExpiredUsernames();
    }

    public function clearExpired()
    {
        $this->emailVerificationRepository->clearExpired();
    }

    public function generateToken(string $username, string $type) : string
    {
        return $this->emailVerificationRepository->generateToken($username, $type);
    }

    public function getByToken(string $token) : ?EmailVerification
    {
        return $this->emailVerificationRepository->getByToken($token);
    }

    public function remove(EmailVerification $emailVerification)
    {
        $this->emailVerificationRepository->remove($emailVerification);
    }
}