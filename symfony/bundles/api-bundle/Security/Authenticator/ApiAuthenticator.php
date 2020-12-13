<?php

namespace Bundles\ApiBundle\Security\Authenticator;

use Bundles\ApiBundle\Entity\Token;
use Bundles\ApiBundle\Enum\Error;
use Bundles\ApiBundle\Exception\ApiAuthenticationException;
use Bundles\ApiBundle\Manager\TokenManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class ApiAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var TokenManager
     */
    private $tokenManager;

    public function __construct(TokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return Error::AUTHENTICATION_REQUIRED()->getResponse();
    }

    public function supports(Request $request)
    {
        return preg_match('|^/api/|', $request->getPathInfo());
    }

    public function getCredentials(Request $request)
    {
        if (!$authorization = $request->headers->get('Authorization')) {
            throw new ApiAuthenticationException(Error::AUTHENTICATION_NO_AUTHORIZATION());
        }

        $matches = [];
        if (!preg_match('|^Bearer (?P<uuid>[0-9a-f]{8}\-[0-9a-f]{4}\-4[0-9a-f]{3}\-[89ab][0-9a-f]{3}\-[0-9a-f]{12})$|', $authorization, $matches)) {
            throw new ApiAuthenticationException(Error::AUTHENTICATION_NO_TOKEN());
        }

        if (!$signature = $request->headers->get('X-Signature')) {
            throw new ApiAuthenticationException(Error::AUTHENTICATION_NO_SIGNATURE());
        }

        return [
            'token'     => $this->tokenManager->findToken($matches['uuid']),
            'signature' => $signature,
            'method'    => $request->getMethod(),
            'uri'       => $request->getPathInfo(),
            'body'      => $request->getContent(),
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        /** @var Token $token */
        $token = $credentials['token'];

        if (!$token) {
            return null;
        }

        $this->tokenManager->increaseHitCount($token);

        return $userProvider->loadUserByUsername(
            $token->getUsername()
        );
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return $credentials['signature'] === $credentials['token']->sign($credentials['method'], $credentials['uri'], $credentials['body']);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($exception instanceof ApiAuthenticationException) {
            return $exception->getError()->getResponse();
        }

        return Error::AUTHENTICATION_FAILED()->getResponse();
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // Go ahead buddy!
    }

    public function supportsRememberMe()
    {
        return false;
    }
}