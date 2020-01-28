<?php

namespace Bundles\PasswordLoginBundle\Security\Authenticator;

use Bundles\PasswordLoginBundle\Entity\Captcha;
use Bundles\PasswordLoginBundle\Entity\User;
use Bundles\PasswordLoginBundle\Form\Type\ConnectType;
use Bundles\PasswordLoginBundle\Traits\ServiceTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;

class FormLoginAuthenticator extends AbstractFormLoginAuthenticator implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    use ServiceTrait;

    public function supports(Request $request)
    {
        if ('/connect' !== $request->getPathInfo()) {
            return false;
        }

        $connectForm = $this
            ->get('form.factory')
            ->create(ConnectType::class)
            ->handleRequest($request);

        if (!$connectForm->isSubmitted()) {
            return false;
        }

        if (!$connectForm->isValid()) {
            $this->decreaseGrace();

            return false;
        }

        return true;
    }

    public function getCredentials(Request $request)
    {
        $connectForm = $this
            ->get('form.factory')
            ->create(ConnectType::class)
            ->handleRequest($request);

        $data = $connectForm->getData();

        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername($credentials['username']);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        $isValid = $this
            ->get('security.password_encoder')
            ->isPasswordValid($user, $credentials['password']);

        if (!$isValid) {
            $this->decreaseGrace();

            throw new BadCredentialsException();
        }

        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        /**
         * @var User $user
         */
        $user = $token->getUser();

        if (!$user->isVerified()) {
            $this->get('session')->getFlashBag()->add('alert', $this->trans('password_login.verify_email.failure'));
            $this->get('security.token_storage')->setToken(null);

            return new RedirectResponse($this->getLoginUrl());
        }

        if ($user->isTrusted()) {
            $ip = $this
                ->get('request_stack')
                ->getMasterRequest()
                ->getClientIp();

            $this
                ->getManager(Captcha::class)
                ->whitelistNow($ip);
        }

        return new RedirectResponse(
            $this->get('router')->generate('home')
        );
    }

    protected function getLoginUrl()
    {
        return $this->get('router')->generate('password_login_connect');
    }

    private function decreaseGrace()
    {
        $this->getManager(Captcha::class)->decreaseGrace(
            $this->get('request_stack')->getMasterRequest()->getClientIp()
        );
    }
}