<?php

namespace App\Security\Helper;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Lightweight replacement of the now-removed Symfony\Bundle\SecurityBundle\Security
 * helper. Delegates user/token retrieval to the standard token storage.
 */
class Security
{
    public const ACCESS_DENIED_ERROR  = '_security.403_error';
    public const AUTHENTICATION_ERROR = '_security.last_error';
    public const LAST_USERNAME        = '_security.last_username';
    public const MAX_USERNAME_LENGTH  = 4096;

    private TokenStorageInterface $tokenStorage;
    private ?AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(TokenStorageInterface $tokenStorage,
        ?AuthorizationCheckerInterface $authorizationChecker = null)
    {
        $this->tokenStorage         = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getUser(): ?UserInterface
    {
        $token = $this->getToken();

        if (null === $token) {
            return null;
        }

        $user = $token->getUser();
        return $user instanceof UserInterface ? $user : null;
    }

    public function getToken(): ?TokenInterface
    {
        return $this->tokenStorage->getToken();
    }

    public function isGranted($attributes, mixed $subject = null): bool
    {
        if (null === $this->authorizationChecker) {
            return false;
        }

        // Preserve Sf 5 behavior: explicit error when no token is set, instead of
        // silently returning false (which Sf 6's AuthorizationChecker now does).
        if (null === $this->tokenStorage->getToken()) {
            throw new AuthenticationCredentialsNotFoundException(
                'The token storage contains no authentication token. One possible reason may be that there is no firewall configured for this URL.'
            );
        }

        return $this->authorizationChecker->isGranted($attributes, $subject);
    }
}
