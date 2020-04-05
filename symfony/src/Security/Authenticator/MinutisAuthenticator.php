<?php

namespace App\Security\Authenticator;

use App\Entity\Volunteer;
use App\Manager\UserInformationManager;
use App\Manager\VolunteerManager;
use Exception;
use Firebase\JWT\JWT;
use Goutte\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
     * @var UserInformationManager
     */
    private $userInformationManager;

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
     * @var Volunteer
     */
    private $volunteer;

    /**
     * @param VolunteerManager       $volunteerManager
     * @param UserInformationManager $userInformationManager
     * @param RouterInterface        $router
     * @param KernelInterface        $kernel
     * @param LoggerInterface|null   $logger
     */
    public function __construct(VolunteerManager $volunteerManager,
        UserInformationManager $userInformationManager,
        RouterInterface $router,
        KernelInterface $kernel,
        LoggerInterface $logger = null)
    {
        $this->volunteerManager       = $volunteerManager;
        $this->userInformationManager = $userInformationManager;
        $this->router                 = $router;
        $this->kernel                 = $kernel;
        $this->logger                 = $logger ?? new NullLogger();
    }

    public function supports(Request $request)
    {
        $support = getenv('IS_REDCROSS')
            && getenv('MINUTIS_JWT_PUBLIC_KEY_URL')
            && '/auth' === $request->getPathInfo()
            && 'POST' === $request->getMethod();

        if (!$support) {
            $this->logger->debug('Minutis authenticator: request not supported.', [
                'public_key' => getenv('MINUTIS_JWT_PUBLIC_KEY_URL'),
                'path_info' => $request->getPathInfo(),
                'method' => $request->getMethod(),
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
            $decoded = (array)JWT::decode($jwt, $this->getMinutisPublicKey(), ['RS256']);
        } catch (Exception $e) {
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

        // Seek for a volunteer attached to that nivol
        $nivol     = ltrim($decoded['nivol'], '0');
        $volunteer = $this->volunteerManager->findOneByNivol($nivol);
        if (null === $volunteer) {
            $this->logger->warning('Minutis authenticator: nivol not associated with a volunteer', [
                'nivol' => $nivol,
            ]);

            throw new BadCredentialsException();
        }

        if (!$volunteer->isEnabled()) {
            $this->logger->warning('Minutis authenticator: nivol associated to a disabled volunteer', [
                'nivol' => $nivol,
            ]);

            throw new BadCredentialsException();
        }

        // Will be used by onAuthenticationFailure handler
        $this->volunteer = $volunteer;

        // Seek for a RedCall user attached to that volunteer
        $userInformation = $this->userInformationManager->findOneByNivol($nivol);
        if (null === $userInformation) {
            $this->logger->info('Minutis authenticator: a volunteer without RedCall access clicked on Minutis link', [
                'nivol' => $nivol,
            ]);

            throw new BadCredentialsException();
        }

        $this->logger->info('Minutis authenticator: successfully connected user', [
            'user' => $userInformation->getUser()->getUsername(),
        ]);

        return $userInformation->getUser();
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($this->volunteer) {
            return new RedirectResponse(
                $this->router->generate('infos', [
                    'nivol' => $this->volunteer->getNivol(),
                ])
            );
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return new RedirectResponse(
            $this->router->generate('home')
        );
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        $url = $this->router->generate('password_login_connect');

        if (getenv('IS_REDCROSS') && 'dev' !== $this->kernel->getEnvironment()) {
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
