<?php

namespace App\Security\Authenticator;

use App\Entity\Volunteer;
use App\Manager\VolunteerSessionManager;
use App\Provider\OAuth\GoogleConnect\GoogleConnectInterface;
use App\Tools\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class GoogleConnectAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private GoogleConnectInterface $googleConnect;
    private VolunteerSessionManager $volunteerSessionManager;
    private RouterInterface $router;

    /** Used to create a session on auth failure */
    private ?Volunteer $volunteer = null;

    public function __construct(GoogleConnectInterface $googleConnect,
        VolunteerSessionManager $volunteerSessionManager,
        RouterInterface $router)
    {
        $this->googleConnect           = $googleConnect;
        $this->volunteerSessionManager = $volunteerSessionManager;
        $this->router                  = $router;
    }

    public function supports(Request $request): ?bool
    {
        foreach (['state', 'code'] as $parameter) {
            if (!$request->query->has($parameter)) {
                return false;
            }
        }

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $volunteer = $this->googleConnect->verify($request);

        if (null === $volunteer || !$volunteer->isEnabled()) {
            throw new BadCredentialsException();
        }

        $this->volunteer = $volunteer;

        $user = $volunteer->getUser();
        if (null === $user) {
            throw new BadCredentialsException();
        }

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

        return new RedirectResponse(
            $this->router->generate('home')
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($url = $this->googleConnect->getRedirectAfterAuthenticationUri($request)) {
            return new RedirectResponse($url);
        }

        return new RedirectResponse(
            $this->router->generate('home')
        );
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            $this->googleConnect->getAuthorizationUri(
                Url::getAbsolute($this->router->generate('home'))
            )
        );
    }
}
