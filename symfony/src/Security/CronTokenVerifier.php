<?php

namespace App\Security;

use Google\Auth\AccessToken;

/**
 * Verifies the OIDC ID token that Cloud Scheduler attaches to cron
 * requests when the app runs on Cloud Run (where the X-Appengine-Cron
 * header cannot be trusted).
 */
class CronTokenVerifier
{
    private $accessToken;

    public function __construct(?AccessToken $accessToken = null)
    {
        $this->accessToken = $accessToken ?: new AccessToken();
    }

    public function verify(string $idToken) : bool
    {
        $audience      = getenv('WEBSITE_URL');
        $expectedEmail = getenv('CRON_INVOKER_SA');

        if (!$audience || !$expectedEmail) {
            return false;
        }

        try {
            $payload = $this->accessToken->verify($idToken, [
                'audience' => $audience,
                'issuer'   => 'https://accounts.google.com',
            ]);
        } catch (\Throwable $e) {
            return false;
        }

        if (!is_array($payload)) {
            return false;
        }

        return ($payload['email'] ?? null) === $expectedEmail
               && true === ($payload['email_verified'] ?? false);
    }
}
