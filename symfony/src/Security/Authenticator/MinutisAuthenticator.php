<?php

namespace App\Security\Authenticator;

use App\Entity\Volunteer;
use App\Manager\VolunteerManager;
use App\Manager\VolunteerSessionManager;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class MinutisAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private VolunteerManager $volunteerManager;
    private VolunteerSessionManager $volunteerSessionManager;
    private RouterInterface $router;
    private LoggerInterface $logger;
    private KernelInterface $kernel;
    private RequestStack $requestStack;

    /** Used by onAuthenticationFailure */
    private ?Volunteer $volunteer = null;

    public function __construct(VolunteerManager $volunteerManager,
        VolunteerSessionManager $volunteerSessionManager,
        RouterInterface $router,
        LoggerInterface $logger,
        KernelInterface $kernel,
        RequestStack $requestStack)
    {
        $this->volunteerManager        = $volunteerManager;
        $this->volunteerSessionManager = $volunteerSessionManager;
        $this->router                  = $router;
        $this->logger                  = $logger;
        $this->kernel                  = $kernel;
        $this->requestStack            = $requestStack;
    }

    public function supports(Request $request): ?bool
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

    public function authenticate(Request $request): Passport
    {
        $jwt = $request->request->get('jwt');

        $this->logger->debug('Minutis authenticator: received a JWT.', [
            'jwt' => $jwt,
        ]);

        try {
            $decoded = (array) JWT::decode($jwt, new Key($this->getMinutisPublicKey(), 'RS256'));
        } catch (\Throwable $e) {
            $this->logger->warning('Minutis authenticator: unable to decode JWT', [
                'token'     => $jwt,
                'exception' => $e->getMessage(),
            ]);

            throw new BadCredentialsException();
        }

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

        $externalId = ltrim($decoded['nivol'], '0');
        $volunteer  = $this->volunteerManager->findOneByExternalId($externalId);

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

        $this->volunteer = $volunteer;

        $user = $volunteer->getUser();
        if (null === $user) {
            $this->logger->info('Minutis authenticator: a volunteer without RedCall access clicked on Minutis link', [
                'external-id' => $externalId,
            ]);

            throw new BadCredentialsException();
        }

        $this->logger->info('Minutis authenticator: successfully connected user', [
            'user' => $user->getUserIdentifier(),
        ]);

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), function () use ($user) {
                return $user;
            })
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($this->volunteer && $this->volunteer->isEnabled()) {
            $sessionId = $this->volunteerSessionManager->createSession($this->volunteer);

            return new RedirectResponse(
                $this->router->generate('space_home', [
                    'sessionId' => $sessionId,
                ])
            );
        }

        return null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $session = $this->getSession();

        $route = $session->get('auth_redirect', [
            'route'        => 'home',
            'route_params' => [],
        ]);

        $session->remove('auth_redirect');

        $response = new RedirectResponse(
            $this->router->generate($route['route'], $route['route_params'])
        );

        $response->headers->setCookie(
            new Cookie('auth_method', 'minutis', strtotime('Sat, 10-Jul-2100 06:37:00 +0200'))
        );

        return $response;
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $session = $this->getSession();
        $session->set('auth_redirect', [
            'route'        => $request->attributes->get('_route'),
            'route_params' => $request->attributes->get('_route_params'),
        ]);

        $url = $this->router->generate('password_login_connect');

        return new RedirectResponse($url);
    }

    private function getSession(): Session
    {
        return $this->requestStack->getSession();
    }

    private function getMinutisPublicKey(): string
    {
        $client = new HttpBrowser(HttpClient::create());

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
