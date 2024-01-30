<?php

namespace App\Provider\OAuth\GoogleConnect;

use App\Entity\Volunteer;
use App\Enum\Platform;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use App\Model\OAuthUser;
use App\Tools\Url;
use App\Validator\Constraints\WhitelistedRedirectUrl;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleConnect implements GoogleConnectInterface
{
    private const AUTHORIZATION_URL = 'https://accounts.google.com/o/oauth2/auth';
    private const ACCESS_TOKEN_URL  = 'https://accounts.google.com/o/oauth2/token';
    private const INFORMATION_URL   = 'https://www.googleapis.com/oauth2/v1/userinfo';
    private const SCOPES            = 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile';

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var HttpClientInterface
     */
    private $client;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var ParameterBagInterface
     */
    private $parameters;

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @var UserManager
     */
    private $userManager;

    public function __construct(RouterInterface $router,
        HttpClientInterface $client,
        ValidatorInterface $validator,
        VolunteerManager $volunteerManager,
        LoggerInterface $logger,
        CsrfTokenManagerInterface $csrfTokenManager,
        ParameterBagInterface $parameters,
        UserManager $userManager,
        string $clientId,
        string $clientSecret)
    {
        $this->router           = $router;
        $this->client           = $client;
        $this->validator        = $validator;
        $this->volunteerManager = $volunteerManager;
        $this->logger           = $logger;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->parameters       = $parameters;
        $this->clientId         = $clientId;
        $this->clientSecret     = $clientSecret;
        $this->userManager      = $userManager;
    }

    public function getAuthorizationUri(string $redirectUri)
    {
        return sprintf('%s?%s', self::AUTHORIZATION_URL, http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->getRedirectUri(),
            'scope'         => self::SCOPES,
            'state'         => \json_encode([
                'csrf' => $this->csrfTokenManager->getToken('google_csrf')->getValue(),
                'uri'  => $redirectUri,
            ]),
        ]));
    }

    public function verify(Request $request) : ?Volunteer
    {
        // Checking that mandatory parameters exist
        if (!$this->isQueryStringValid($request)) {
            return null;
        }

        // Checking that the CSRF token is valid
        if (!$this->isCsrfTokenValid($request)) {
            return null;
        }

        // Checking that the URL was not forged by the user
        if (!$this->isRedirectUriValid($request)) {
            return null;
        }

        // Exchanging the Google authorization code for an oauth access token
        if (null === ($token = $this->getAccessToken($request))) {
            return null;
        }

        // Using the token to get the oauth user
        if (null === ($oAuthUser = $this->getOauthUser($token))) {
            return null;
        }

        $user = $this->userManager->findOneByUsernameAndPlatform(Platform::FR, $oAuthUser->getEmail());

        if (null === $user) {
            return $this->volunteerManager->getVolunteerFromOauth($oAuthUser);
        }

        if ($user && $volunteer = $user->getVolunteer()) {
            if (!$volunteer->isEnabled()) {
                $volunteer->setEnabled(true);
                $this->volunteerManager->save($volunteer);
            }

            return $user->getVolunteer();
        }

        return null;
    }

    public function getRedirectAfterAuthenticationUri(Request $request) : ?string
    {
        $state       = \json_decode($request->query->get('state'), true);
        $redirectUri = $state['uri'] ?? null;

        $isValid = $redirectUri && 0 === count($this->validator->validate($redirectUri, new WhitelistedRedirectUrl()));

        return $isValid ? $redirectUri : null;
    }

    private function isQueryStringValid(Request $request) : bool
    {
        foreach (['state', 'code'] as $parameter) {
            if (!$request->query->has($parameter)) {
                $this->logger->warning('Google authenticator: verification query string is missing mandatory parameters', [
                    'missing_parameter' => $parameter,
                ]);

                return false;
            }
        }

        return true;
    }

    private function isCsrfTokenValid(Request $request) : bool
    {
        $state  = \json_decode($request->query->get('state'), true);
        $secret = ($state['csrf'] ?? false);

        if (!$secret) {
            return false;
        }

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('google_csrf', $secret))) {
            $this->logger->warning('Google authenticator: invalid csrf token');

            return false;
        }

        return true;
    }

    private function isRedirectUriValid(Request $request) : bool
    {
        if (!$redirectUri = $this->getRedirectAfterAuthenticationUri($request)) {
            $this->logger->warning('Google authenticator: non-whitelisted URI provided', [
                'uri' => $redirectUri,
            ]);

            return false;
        }

        return true;
    }

    private function getAccessToken(Request $request) : ?string
    {
        $response = $this->client->request(
            Request::METHOD_POST,
            self::ACCESS_TOKEN_URL,
            [
                'body' => [
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'code'          => $request->query->get('code'),
                    'grant_type'    => 'authorization_code',
                    'redirect_uri'  => $this->getRedirectUri(),
                ],
            ],
        );

        try {
            $result = json_decode($response->getContent(), true);

            return $result['access_token'];
        } catch (HttpExceptionInterface $exception) {
            $this->logger->warning('Google authenticator: cannot get an access token', [
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function getOauthUser(string $token) : ?OAuthUser
    {
        $response = $this->client->request(
            Request::METHOD_GET,
            self::INFORMATION_URL,
            [
                'headers' => [
                    'Authorization' => sprintf('Bearer %s', $token),
                ],
            ],
        );

        try {
            $result = json_decode($response->getContent(), true);

            if (!($result['verified_email'] ?? false)) {
                $this->logger->warning('Google authenticator: user has a non-verified email');

                return null;
            }

            $oauthUser = new OAuthUser();
            $oauthUser->setEmail($result['email']);
            $oauthUser->setFirstname($result['given_name'] ?? null);
            $oauthUser->setLastname($result['family_name'] ?? null);
            $oauthUser->setPictureUrl($result['picture'] ?? null);

            return $oauthUser;
        } catch (HttpExceptionInterface $exception) {
            $this->logger->warning('Google authenticator: cannot get profile information', [
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function getRedirectUri() : string
    {
        if ('dev' === getenv('APP_ENV')) {
            return Url::getAbsolute(
                $this->router->generate('google_verify')
            );
        } else {
            return $this->parameters->get('long_url').$this->router->generate('google_verify');
        }
    }
}