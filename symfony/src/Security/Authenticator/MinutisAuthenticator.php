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
     * @var Volunteer
     */
    private $volunteer;

    /**
     * @param VolunteerManager       $volunteerManager
     * @param UserInformationManager $userInformationManager
     * @param RouterInterface        $router
     * @param LoggerInterface|null   $logger
     */
    public function __construct(VolunteerManager $volunteerManager,
        UserInformationManager $userInformationManager,
        RouterInterface $router,
        LoggerInterface $logger = null)
    {
        $this->volunteerManager       = $volunteerManager;
        $this->userInformationManager = $userInformationManager;
        $this->router                 = $router;
        $this->logger                 = $logger ?? new NullLogger();
    }

    public function supports(Request $request)
    {
        return getenv('MINUTIS_JWT_PUBLIC_KEY_URL')
               && '/auth' === $request->getPathInfo()
               && 'POST' === $request->getMethod();
    }

    public function getCredentials(Request $request)
    {
        return $request->request->get('jwt');
    }

    public function getUser($jwt, UserProviderInterface $userProvider)
    {
        // Decode and verify JWT token
        try {
            $decoded = (array)JWT::decode($jwt, $this->getMinutisPublicKey(), ['RS256']);
        } catch (Exception $e) {
            // Either invalid JWT, invalid algo, expired token...
            $this->logger->warning('Unable to decode JWT', [
                'token'     => $jwt,
                'exception' => $e->getMessage(),
            ]);

            throw new BadCredentialsException();
        }

        // Seek for a nivol in payload
        foreach (['iat', 'exp', 'nivol'] as $requiredKey) {
            if (!array_key_exists($requiredKey, $decoded)) {
                $this->logger->warning('Key not given in JWT', [
                    'key'   => $requiredKey,
                    'token' => $jwt,
                ]);

                throw new BadCredentialsException();
            }
        }

        // Seek for a volunteer attached to that nivol
        $nivol     = $decoded['nivol'];
        $volunteer = $this->volunteerManager->findOneByNivol($nivol);
        if (null === $volunteer) {
            $this->logger->warning('Nivol not associated with a volunteer', [
                'nivol' => $nivol,
            ]);

            throw new BadCredentialsException();
        }

        // Will be used by onAuthenticationFailure handler
        $this->volunteer = $volunteer;

        // Seek for a RedCall user attached to that volunteer
        $userInformation = $this->userInformationManager->findOneByNivol($nivol);
        if (null === $userInformation) {
            $this->logger->info('A volunteer without RedCall access clicked on Minutis link', [
                'nivol' => $nivol,
            ]);

            throw new BadCredentialsException();
        }

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
        // The login form guard authenticator is called by default.
    }

    public function supportsRememberMe()
    {
        return false;
    }

    private function getMinutisPublicKey()
    {
        $client = new Client();

        $client->request('GET', getenv('MINUTIS_JWT_PUBLIC_KEY_URL'));

        return $client->getResponse()->getContent();
    }
}
