<?php

namespace App\Security\Authenticator;

use App\Entity\Volunteer;
use App\Manager\VolunteerSessionManager;
use App\Provider\OAuth\GoogleConnect\GoogleConnectInterface;
use App\Tools\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class GoogleConnectAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var GoogleConnectInterface
     */
    private $googleConnect;

    /**
     * @var VolunteerSessionManager
     */
    private $volunteerSessionManager;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * Used to create a session
     *
     * @var Volunteer
     */
    private $volunteer;

    public function __construct(GoogleConnectInterface $googleConnect,
        VolunteerSessionManager $volunteerSessionManager,
        RouterInterface $router)
    {
        $this->googleConnect           = $googleConnect;
        $this->volunteerSessionManager = $volunteerSessionManager;
        $this->router                  = $router;
    }

    public function supports(Request $request)
    {
        foreach (['state', 'code'] as $parameter) {
            if (!$request->query->has($parameter)) {
                return false;
            }
        }

        return true;
    }

    public function getCredentials(Request $request)
    {
        return $request;
    }

    public function getUser($request, UserProviderInterface $userProvider)
    {
        $volunteer = $this->googleConnect->verify($request);

        if (null === $volunteer || !$volunteer->isEnabled()) {
            throw new BadCredentialsException();
        }

        // Will be used by onAuthenticationFailure handler
        $this->volunteer = $volunteer;

        // Seek for a RedCall user attached to that volunteer
        if (null === $user = $volunteer->getUser()) {
            throw new BadCredentialsException();
        }

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

        return new RedirectResponse(
            $this->router->generate('home')
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        if ($url = $this->googleConnect->getRedirectAfterAuthenticationUri($request)) {
            return new RedirectResponse($url);
        }

        return new RedirectResponse(
            $this->router->generate('home')
        );
    }

    public function supportsRememberMe()
    {
        return false;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse(
            $this->googleConnect->getAuthorizationUri(
                Url::getAbsolute($this->router->generate('home'))
            )
        );
    }
}
