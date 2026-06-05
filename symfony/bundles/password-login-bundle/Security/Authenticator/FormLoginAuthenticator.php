<?php

namespace Bundles\PasswordLoginBundle\Security\Authenticator;

use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Bundles\PasswordLoginBundle\Form\Type\ConnectType;
use Bundles\PasswordLoginBundle\Manager\CaptchaManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    private CaptchaManager $captchaManager;
    private FormFactoryInterface $formFactory;
    private TokenStorageInterface $tokenStorage;
    private TranslatorInterface $translator;
    private RequestStack $requestStack;
    private RouterInterface $router;
    private UserProviderInterface $userProvider;
    private LoggerInterface $logger;
    private string $homeRoute;

    public function __construct(CaptchaManager $captchaManager,
        FormFactoryInterface $formFactory,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        RequestStack $requestStack,
        RouterInterface $router,
        UserProviderInterface $userProvider,
        ?LoggerInterface $logger = null,
        string $homeRoute = 'home')
    {
        $this->captchaManager = $captchaManager;
        $this->formFactory    = $formFactory;
        $this->tokenStorage   = $tokenStorage;
        $this->translator     = $translator;
        $this->requestStack   = $requestStack;
        $this->router         = $router;
        $this->userProvider   = $userProvider;
        $this->logger         = $logger ?? new NullLogger();
        $this->homeRoute      = $homeRoute;
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $session = $this->getSession();
        $session->set('auth_redirect', [
            'route'        => $request->attributes->get('_route'),
            'route_params' => $request->attributes->get('_route_params'),
        ]);

        return new RedirectResponse($this->getLoginUrl($request));
    }

    public function supports(Request $request): bool
    {
        if ('/connect' !== $request->getPathInfo()) {
            return false;
        }

        $this->logger->info('FormLoginAuthenticator: supports() entered', [
            'method' => $request->getMethod(),
            'ip'     => $request->getClientIp(),
        ]);

        $connectForm = $this
            ->formFactory
            ->create(ConnectType::class)
            ->handleRequest($request);

        if (!$connectForm->isSubmitted()) {
            // Fires on every GET /connect (page render). Keep at DEBUG to
            // avoid noise; this is normal, not a failure.
            $this->logger->debug('FormLoginAuthenticator: form not submitted, skipping', [
                'method'    => $request->getMethod(),
                'post_keys' => array_keys($request->request->all()),
            ]);

            return false;
        }

        if (!$connectForm->isValid()) {
            $session = $this->getSession();
            $errors  = [];
            foreach ($connectForm->getErrors(true) as $error) {
                $session->getFlashBag()->add('alert', $error->getMessage());
                $origin   = $error->getOrigin();
                $errors[] = [
                    'field'   => $origin ? $origin->getName() : '(form)',
                    'message' => $error->getMessage(),
                ];
            }

            $this->logger->warning('FormLoginAuthenticator: form validation failed', [
                'errors' => $errors,
                'ip'     => $request->getClientIp(),
            ]);

            $this->decreaseGrace();

            return false;
        }

        $this->logger->info('FormLoginAuthenticator: form valid, will authenticate');

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $connectForm = $this
            ->formFactory
            ->create(ConnectType::class)
            ->handleRequest($request);

        $data     = $connectForm->getData();
        $username = $data['username'];
        $password = $data['password'];

        if (null === $username || null === $password) {
            $this->logger->warning('FormLoginAuthenticator: missing username or password', [
                'username_set' => null !== $username,
                'password_set' => null !== $password,
            ]);

            throw new BadCredentialsException();
        }

        $this->logger->info('FormLoginAuthenticator: building passport', [
            'username' => $username,
        ]);

        return new Passport(
            new UserBadge($username, function ($identifier) {
                $user = $this->userProvider->loadUserByIdentifier($identifier);
                if (null === $user) {
                    $this->logger->warning('FormLoginAuthenticator: user not found by identifier', [
                        'identifier' => $identifier,
                    ]);

                    throw new BadCredentialsException();
                }

                $this->logger->info('FormLoginAuthenticator: user loaded from provider', [
                    'username'   => $user->getUserIdentifier(),
                    'is_locked'  => method_exists($user, 'isLocked') ? $user->isLocked() : null,
                    'is_verified'=> method_exists($user, 'isVerified') ? $user->isVerified() : null,
                ]);

                return $user;
            }),
            new PasswordCredentials($password)
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $previous = $exception->getPrevious();

        $this->logger->warning('FormLoginAuthenticator: authentication failed', [
            'exception'         => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'previous'          => $previous ? get_class($previous) : null,
            'previous_message'  => $previous ? $previous->getMessage() : null,
            'ip'                => $request->getClientIp(),
        ]);

        $this->decreaseGrace();

        return new RedirectResponse($this->getLoginUrl($request));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var AbstractUser $user */
        $user = $token->getUser();

        $session = $this->getSession();

        if (!$user->isVerified()) {
            $this->logger->warning('FormLoginAuthenticator: user is not verified, redirecting back to login', [
                'username' => $user->getUserIdentifier(),
            ]);

            $session->getFlashBag()->add('alert', $this->translator->trans('password_login.verify_email.failure'));
            $this->tokenStorage->setToken();

            return new RedirectResponse($this->getLoginUrl($request));
        }

        $this->logger->info('FormLoginAuthenticator: login successful', [
            'username'  => $user->getUserIdentifier(),
            'isTrusted' => $user->isTrusted(),
        ]);

        if ($user->isTrusted()) {
            $ip = $this
                ->requestStack
                ->getMainRequest()
                ->getClientIp();

            $this
                ->captchaManager
                ->whitelistNow($ip);
        }

        $route = $session->get('auth_redirect', [
            'route'        => $this->homeRoute,
            'route_params' => [],
        ]);

        $session->remove('auth_redirect');

        $response = new RedirectResponse(
            $this->router->generate($route['route'], $route['route_params'])
        );

        $response->headers->setCookie(
            new Cookie('auth_method', 'password_login', strtotime('Sat, 10-Jul-2100 06:37:00 +0200'))
        );

        return $response;
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate('password_login_connect');
    }

    private function decreaseGrace(): void
    {
        $this->captchaManager->decreaseGrace(
            $this->requestStack->getMainRequest()->getClientIp()
        );
    }

    private function getSession(): Session
    {
        return $this->requestStack->getSession();
    }
}
