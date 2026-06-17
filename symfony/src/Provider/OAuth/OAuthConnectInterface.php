<?php

namespace App\Provider\OAuth;

use App\Model\OAuthUser;
use Symfony\Component\HttpFoundation\Request;

interface OAuthConnectInterface
{
    /**
     * Returns the URL at which the user should be redirected to
     * Google in order to grant Dot the permission to access
     * his/her basic user information.
     */
    public function getAuthorizationUri(string $redirectUri);

    /**
     * Once user granted/denied Dot to access profile information,
     * Google redirects user to a given technical redirect uri with
     * information given in the query string (most importantly,
     * an authorization code that can be exchanged for an access
     * token).
     *
     * Returns the authenticated OAuth identity (verified email + profile), or
     * null if verification failed. Resolving the matching RedCall user /
     * volunteer is the caller's responsibility.
     */
    public function verify(Request $request) : ?OAuthUser;

    /**
     * This method is not required by the OAuth2 flow, but more
     * by the API-driven design.
     *
     * When user requests to sign-in, client provides the redirect URI
     * at which user should be sent once login succeeded or failed (see
     * Dot\AccountBundle\Facade\GoogleConnect\GoogleConnectRequest)
     *
     * As we are stateless, we store this URI into the OAuth "state"
     * parameter, along with the csrf token, and should extract it
     * from the request in this method.
     */
    public function getRedirectAfterAuthenticationUri(Request $request) : ?string;
}