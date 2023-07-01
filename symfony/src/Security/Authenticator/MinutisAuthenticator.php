<?php

namespace App\Security\Authenticator;

use App\Entity\Volunteer;
use App\Manager\PlatformConfigManager;
use App\Manager\VolunteerManager;
use App\Manager\VolunteerSessionManager;
use Firebase\JWT\JWT;
use Goutte\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class MinutisAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var VolunteerSessionManager
     */
    private $volunteerSessionManager;

    /**
     * @var PlatformConfigManager
     */
    private $platformManager;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var Volunteer
     */
    private $volunteer;

    public function __construct(VolunteerManager $volunteerManager,
        VolunteerSessionManager $volunteerSessionManager,
        PlatformConfigManager $platformManager,
        RouterInterface $router,
        LoggerInterface $logger,
        KernelInterface $kernel,
        SessionInterface $session)
    {
        $this->volunteerManager        = $volunteerManager;
        $this->volunteerSessionManager = $volunteerSessionManager;
        $this->platformManager         = $platformManager;
        $this->router                  = $router;
        $this->logger                  = $logger;
        $this->kernel                  = $kernel;
        $this->session                 = $session;
    }

    public function supports(Request $request)
    {
        $support = getenv('MINUTIS_JWT_PUBLIC_KEY_URL')
                   && '/auth' === $request->getPathInfo()
                   && 'POST' === $request->getMethod();

        if (!$support) {
            $this->logger->debug('Minutis authenticator: request not supported.', [
                'public_key' => getenv('MINUTIS_JWT_PUBLIC_KEY_URL'),
                'path_info'  => $request->getPathInfo(),
                'method'     => $request->getMethod(),
            ]);
        }

        return $support;
    }

    public function getCredentials(Request $request)
    {
        $jwt = $request->request->get('jwt');

        $this->logger->debug('Minutis authenticator: received a JWT.', [
            'jwt' => $jwt,
        ]);

        return $jwt;
    }

    public function getUser($jwt, UserProviderInterface $userProvider)
    {
        // Decode and verify JWT token
        try {
            $decoded = (array) JWT::decode($jwt, $this->getMinutisPublicKey(), ['RS256']);
        } catch (\Throwable $e) {
            // Either invalid JWT, invalid algo, expired token...
            $this->logger->warning('Minutis authenticator: unable to decode JWT', [
                'token'     => $jwt,
                'exception' => $e->getMessage(),
            ]);

            throw new BadCredentialsException();
        }

        // Seek for a nivol in payload
        foreach (['exp', 'nivol'] as $requiredKey) {
            if (!array_key_exists($requiredKey, $decoded)) {
                $this->logger->warning('Minutis authenticator: key not given in JWT', [
                    'key'   => $requiredKey,
                    'token' => $jwt,
                ]);

                throw new BadCredentialsException();
            }
        }

        $this->logger->notice('Received valid Minutis authentication', [
            'decoded' => $decoded,
        ]);

        // Seek for a volunteer attached to that nivol
        // TODO / SECURITY: if 2 nivols are the same in 2 distinct platforms, a volunteer could connect to someone else's account
        $volunteer = null;
        foreach ($this->platformManager->getPlatformChoices() as $platform) {
            $externalId = ltrim($decoded['nivol'], '0');
            $volunteer  = $this->volunteerManager->findOneByExternalId($platform, $externalId);
            if ($volunteer) {
                break;
            }
        }

        if (null === $volunteer) {
            $this->logger->warning('Minutis authenticator: external id not associated with a volunteer', [
                'external-id' => $externalId,
            ]);

            throw new BadCredentialsException();
        }

        if (!$volunteer->isEnabled()) {
            $this->logger->warning('Minutis authenticator: external id associated to a disabled volunteer', [
                'external-id' => $externalId,
            ]);

            throw new BadCredentialsException();
        }

        // Will be used by onAuthenticationFailure handler
        $this->volunteer = $volunteer;

        // Seek for a RedCall user attached to that volunteer
        $user = $volunteer->getUser();
        if (null === $user) {
            $this->logger->info('Minutis authenticator: a volunteer without RedCall access clicked on Minutis link', [
                'external-id' => $externalId,
            ]);

            throw new BadCredentialsException();
        }

        $this->logger->info('Minutis authenticator: successfully connected user', [
            'user' => $user->getUsername(),
        ]);

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($this->volunteer && $this->volunteer->isEnabled()) {
            $sessionId = $this->volunteerSessionManager->createSession($this->volunteer);

            return new RedirectResponse(
                $this->router->generate('space_home', [
                    'sessionId' => $sessionId,
                ])
            );
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $route = $this->session->get('auth_redirect', [
            'route'        => 'home',
            'route_params' => [],
        ]);

        $this->session->remove('auth_redirect');

        $response = new RedirectResponse(
            $this->router->generate($route['route'], $route['route_params'])
        );

        $response->headers->setCookie(
            new Cookie('auth_method', 'minutis', strtotime('Sat, 10-Jul-2100 06:37:00 +0200'))
        );

        return $response;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        $this->session->set('auth_redirect', [
            'route'        => $request->attributes->get('_route'),
            'route_params' => $request->attributes->get('_route_params'),
        ]);

        $url = $this->router->generate('password_login_connect');

        if ('dev' !== $this->kernel->getEnvironment()
            && 'minutis' !== $request->cookies->get('auth_method')) {
            $url = getenv('MINUTIS_URL');
        }

        return new RedirectResponse($url);
    }

    public function supportsRememberMe()
    {
        return false;
    }

    private function getMinutisPublicKey()
    {
        $client = new Client();

        $client->request('GET', getenv('MINUTIS_JWT_PUBLIC_KEY_URL'));

        $key = $client->getResponse()->getContent();

        $this->logger->info('Minutis authenticator: successfully fetched Minutis public key', [
            'key' => $key,
        ]);

        return sprintf(
            "-----BEGIN PUBLIC KEY-----\n%s\n-----END PUBLIC KEY-----",
            str_pad($key, 64, "\n")
        );
    }
}
