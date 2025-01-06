<?php

namespace App\Security\Authenticator;

use App\Form\Type\CodeType;
use App\Manager\ExpirableManager;
use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Bundles\PasswordLoginBundle\Manager\UserManager;
use Bundles\PasswordLoginBundle\Traits\ServiceTrait;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Contracts\Translation\TranslatorInterface;

class NivolAuthenticator extends AbstractFormLoginAuthenticator
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ExpirableManager
     */
    private $expirableManager;

    /**
     * @var UserManager
     */
    private $userManager;


    public function __construct(
        FormFactoryInterface $formFactory,
        SessionInterface $session,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        RouterInterface $router,
        ExpirableManager $expirableManager,
        UserManager $userManager)
    {
        $this->formFactory      = $formFactory;
        $this->session          = $session;
        $this->tokenStorage     = $tokenStorage;
        $this->translator       = $translator;
        $this->router           = $router;
        $this->expirableManager = $expirableManager;
        $this->userManager      = $userManager;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        $this->session->set('auth_redirect', [
            'route'        => $request->attributes->get('_route'),
            'route_params' => $request->attributes->get('_route_params'),
        ]);

        parent::start($request, $authException);
    }

    public function supports(Request $request)
    {
        if ('/code' !== substr($request->getPathInfo(), 0, 5)) {
            return false;
        }

        $codeForm = $this
            ->formFactory
            ->create(CodeType::class)
            ->handleRequest($request);

        if (!$codeForm->isSubmitted()) {
            return false;
        }

        if (!$codeForm->isValid()) {
            return false;
        }

        return true;
    }

    public function getCredentials(Request $request)
    {
        $codeForm = $this
            ->formFactory
            ->create(CodeType::class)
            ->handleRequest($request);

        $data   = $codeForm->getData();
        $params = $request->attributes->get('_route_params');

        return [
            'identifier' => $params['identifier'],
            'code'       => $data['code'],
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $expirable = $this->expirableManager->get($credentials['identifier']);

        if (null === $expirable) {
            throw new BadCredentialsException();
        }

        $user = $this->userManager->find($expirable['user_id']);

        if (null === $user) {
            throw new BadCredentialsException();
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        $expirable = $this->expirableManager->get($credentials['identifier']);

        if (null === $expirable) {
            throw new BadCredentialsException();
        }

        if (strtolower($expirable['code']) !== strtolower($credentials['code'])) {
            throw new BadCredentialsException();
        }

        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        /**
         * @var AbstractUser $user
         */
        $user = $token->getUser();

        if (!$user->isVerified()) {
            $this->session->getFlashBag()->add('alert', $this->translator->trans('password_login.verify_email.failure'));
            $this->tokenStorage->setToken();

            return new RedirectResponse($this->getLoginUrl());
        }

        $route = $this->session->get('auth_redirect', [
            'route'        => 'home',
            'route_params' => [],
        ]);

        $this->session->remove('auth_redirect');

        $response = new RedirectResponse(
            $this->router->generate($route['route'], $route['route_params'])
        );

        return $response;
    }

    protected function getLoginUrl()
    {
        return $this->router->generate('password_login_connect');
    }
}