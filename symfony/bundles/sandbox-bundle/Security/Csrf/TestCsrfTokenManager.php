<?php

namespace Bundles\SandboxBundle\Security\Csrf;

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Test-only CSRF manager. The Sf6 SessionTokenStorage requires a session in
 * RequestStack, which is awkward to share between the test container and the
 * kernel sub-requests. In tests we just want a stable token value that always
 * validates, so we skip the session entirely.
 */
class TestCsrfTokenManager implements CsrfTokenManagerInterface
{
    private const FIXED_VALUE = 'test-csrf-token';

    public function getToken(string $tokenId): CsrfToken
    {
        return new CsrfToken($tokenId, self::FIXED_VALUE);
    }

    public function refreshToken(string $tokenId): CsrfToken
    {
        return new CsrfToken($tokenId, self::FIXED_VALUE);
    }

    public function removeToken(string $tokenId): ?string
    {
        return self::FIXED_VALUE;
    }

    public function isTokenValid(CsrfToken $token): bool
    {
        return self::FIXED_VALUE === $token->getValue();
    }
}
