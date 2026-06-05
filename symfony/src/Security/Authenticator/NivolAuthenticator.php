<?php

namespace App\Security\Authenticator;

use App\Form\Type\CodeType;
use App\Manager\ExpirableManager;
use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Bundles\PasswordLoginBundle\Manager\UserManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Form\FormFactoryInterface;
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
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NivolAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private FormFactoryInterface $formFactory;
    private RequestStack $requestStack;
    private TokenStorageInterface $tokenStorage;
    private TranslatorInterface $translator;
    private RouterInterface $router;
    private ExpirableManager $expirableManager;
    private UserManager $userManager;
    private LoggerInterface $logger;

    public function __construct(
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        RouterInterface $router,
        ExpirableManager $expirableManager,
        UserManager $userManager,
        ?LoggerInterface $logger = null)
    {
        $this->formFactory      = $formFactory;
        $this->requestStack     = $requestStack;
        $this->tokenStorage     = $tokenStorage;
        $this->translator       = $translator;
        $this->router           = $router;
        $this->expirableManager = $expirableManager;
        $this->userManager      = $userManager;
        $this->logger           = $logger ?? new NullLogger();
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

    public function supports(Request $request): ?bool
    {
        if ('/code' !== substr($request->getPathInfo(), 0, 5)) {
            return false;
        }

        $this->logger->info('NivolAuthenticator: supports() entered', [
            'path'   => $request->getPathInfo(),
            'method' => $request->getMethod(),
        ]);

        $codeForm = $this
            ->formFactory
            ->create(CodeType::class)
            ->handleRequest($request);

        if (!$codeForm->isSubmitted()) {
            // Fires on every GET /code/{uuid} page render. Keep at DEBUG.
            $this->logger->debug('NivolAuthenticator: code form not submitted, skipping', [
                'method' => $request->getMethod(),
            ]);

            return false;
        }

        if (!$codeForm->isValid()) {
            $errors = [];
            foreach ($codeForm->getErrors(true) as $error) {
                $origin   = $error->getOrigin();
                $errors[] = [
                    'field'   => $origin ? $origin->getName() : '(form)',
                    'message' => $error->getMessage(),
                ];
            }

            $this->logger->warning('NivolAuthenticator: code form invalid', [
                'errors' => $errors,
            ]);

            return false;
        }

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $codeForm = $this
            ->formFactory
            ->create(CodeType::class)
            ->handleRequest($request);

        $data   = $codeForm->getData();
        $params = $request->attributes->get('_route_params');

        $uuid = $params['uuid'] ?? null;
        $code = $data['code'] ?? null;

        if (null === $uuid || null === $code) {
            $this->logger->warning('NivolAuthenticator: missing uuid or code', [
                'uuid_set' => null !== $uuid,
                'code_set' => null !== $code,
            ]);

            throw new BadCredentialsException();
        }

        $expirable = $this->expirableManager->get($uuid);

        if (null === $expirable) {
            $this->logger->warning('NivolAuthenticator: expirable not found (likely expired)', [
                'uuid' => $uuid,
            ]);

            throw new BadCredentialsException();
        }

        if (strtolower($expirable['code']) !== strtolower($code)) {
            $this->logger->warning('NivolAuthenticator: code mismatch', [
                'uuid' => $uuid,
            ]);

            throw new BadCredentialsException();
        }

        $user = $this->userManager->find($expirable['user_id']);

        if (null === $user) {
            $this->logger->warning('NivolAuthenticator: user from expirable not found', [
                'uuid'    => $uuid,
                'user_id' => $expirable['user_id'],
            ]);

            throw new BadCredentialsException();
        }

        $this->logger->info('NivolAuthenticator: code accepted, building passport', [
            'username' => $user->getUserIdentifier(),
        ]);

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), function () use ($user) {
                return $user;
            })
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $previous = $exception->getPrevious();

        $this->logger->warning('NivolAuthenticator: authentication failed', [
            'exception'         => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'previous'          => $previous ? get_class($previous) : null,
            'previous_message'  => $previous ? $previous->getMessage() : null,
        ]);

        return new RedirectResponse($this->getLoginUrl($request));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var AbstractUser $user */
        $user = $token->getUser();

        $session = $this->getSession();

        if (!$user->isVerified()) {
            $this->logger->warning('NivolAuthenticator: user is not verified, redirecting to login', [
                'username' => $user->getUserIdentifier(),
            ]);

            $session->getFlashBag()->add('alert', $this->translator->trans('password_login.verify_email.failure'));
            $this->tokenStorage->setToken();

            return new RedirectResponse($this->getLoginUrl($request));
        }

        $this->logger->info('NivolAuthenticator: login successful', [
            'username' => $user->getUserIdentifier(),
        ]);

        $route = $session->get('auth_redirect', [
            'route'        => 'home',
            'route_params' => [],
        ]);

        $session->remove('auth_redirect');

        return new RedirectResponse(
            $this->router->generate($route['route'], $route['route_params'])
        );
    }

    private function getLoginUrl(Request $request): string
    {
        return $this->router->generate('password_login_connect');
    }

    private function getSession(): Session
    {
        return $this->requestStack->getSession();
    }
}
