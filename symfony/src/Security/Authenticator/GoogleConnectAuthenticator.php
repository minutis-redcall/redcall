<?php

namespace App\Security\Authenticator;

use App\Entity\Volunteer;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use App\Manager\VolunteerSessionManager;
use App\Provider\OAuth\GoogleConnect\GoogleConnectInterface;
use App\Tools\Url;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class GoogleConnectAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private GoogleConnectInterface $googleConnect;
    private VolunteerSessionManager $volunteerSessionManager;
    private UserManager $userManager;
    private VolunteerManager $volunteerManager;
    private RouterInterface $router;
    private LoggerInterface $logger;

    /** Used to create a session on auth failure */
    private ?Volunteer $volunteer = null;

    public function __construct(GoogleConnectInterface $googleConnect,
        VolunteerSessionManager $volunteerSessionManager,
        UserManager $userManager,
        VolunteerManager $volunteerManager,
        RouterInterface $router,
        ?LoggerInterface $logger = null)
    {
        $this->googleConnect           = $googleConnect;
        $this->volunteerSessionManager = $volunteerSessionManager;
        $this->userManager             = $userManager;
        $this->volunteerManager        = $volunteerManager;
        $this->router                  = $router;
        $this->logger                  = $logger ?? new NullLogger();
    }

    public function supports(Request $request): ?bool
    {
        foreach (['state', 'code'] as $parameter) {
            if (!$request->query->has($parameter)) {
                return false;
            }
        }

        $this->logger->info('GoogleConnectAuthenticator: supports() — state + code present');

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $oAuthUser = $this->googleConnect->verify($request);

        if (null === $oAuthUser) {
            $this->logger->warning('GoogleConnectAuthenticator: Google identity verification failed');

            throw new BadCredentialsException();
        }

        // The RedCall operator is keyed by email (username) — independent of any
        // directory record, so pure-email / Annuaire users (no NIVOL) log in too.
        $user = $this->userManager->findOneByUsername($oAuthUser->getEmail());

        if (null === $user || !$user->isTrusted()) {
            $this->logger->info('GoogleConnectAuthenticator: no trusted RedCall user for this Google identity, falling back to personal space', [
                'email' => $oAuthUser->getEmail(),
            ]);

            // Keep a volunteer (matched by email) around so onAuthenticationFailure
            // can offer the volunteer's personal space.
            $this->volunteer = $this->volunteerManager->getVolunteerFromOauth($oAuthUser);

            throw new BadCredentialsException();
        }

        // Re-enable the matching directory record if it had been disabled.
        if ($user->getExternalId()
            && ($volunteer = $this->volunteerManager->findOneByExternalId($user->getExternalId()))
            && !$volunteer->isEnabled()) {
            $volunteer->setEnabled(true);
            $this->volunteerManager->save($volunteer);
        }

        $this->logger->info('GoogleConnectAuthenticator: building passport', [
            'username' => $user->getUserIdentifier(),
        ]);

        // SSO flow has no _remember_me form field, so we pre-enable the badge
        // to bypass CheckRememberMeConditionsListener's parameter check.
        $rememberMe = new RememberMeBadge();
        $rememberMe->enable();

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), function () use ($user) {
                return $user;
            }),
            [$rememberMe]
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $previous = $exception->getPrevious();

        $this->logger->warning('GoogleConnectAuthenticator: authentication failed', [
            'exception'         => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'previous'          => $previous ? get_class($previous) : null,
            'previous_message'  => $previous ? $previous->getMessage() : null,
        ]);

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
