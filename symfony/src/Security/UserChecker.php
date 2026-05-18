<?php

namespace App\Security;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function checkPreAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isLocked()) {
            $this->logger->warning('UserChecker: pre-auth rejected, user is locked', [
                'username' => $user->getUserIdentifier(),
            ]);

            throw new LockedException('User account is locked.');
        }
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        // No post-auth checks
    }
}
