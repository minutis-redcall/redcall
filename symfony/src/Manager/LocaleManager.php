<?php

namespace App\Manager;

use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LocaleManager
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var string
     */
    private $defaultLocale;

    /**
     * @var array
     */
    private $availableLocales = [];

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var UserManager
     */
    private $userManager;

    public function __construct(RequestStack $requestStack, SessionInterface $session, ParameterBagInterface $parameterBag, TokenStorageInterface $tokenStorage, UserManager $userManager)
    {
        $this->requestStack = $requestStack;
        $this->session = $session;
        $this->defaultLocale = $parameterBag->get('locale');
        $this->availableLocales = $parameterBag->get('locale_list');
        $this->tokenStorage = $tokenStorage;
        $this->userManager = $userManager;
    }

    /**
     * @param string $locale
     */
    public function save(string $locale)
    {
        $this->changeLocale($locale);

        if ($user = $this->getUser()) {
            $user->setLocale($locale);
            $this->userManager->save($user);
        }
    }

    public function restoreFromSession()
    {
        $request = $this->requestStack->getMasterRequest();

        // Set locale from the request
        if ($locale = $request->attributes->get('_locale')) {
            $this->changeLocale($locale);

            return;
        }

        // Set locale from the session
        if ($locale = $this->session->get('_locale')) {
            $this->changeLocale($locale);

            return;
        }

        // Set the default locale
        $request->setLocale($this->defaultLocale);
    }

    public function restoreFromUser()
    {
        // Set locale from user preferences
        $user = $this->getUser();

        if ($user && $user->getLocale()) {
            $this->changeLocale($user->getLocale());
        }
    }

    /**
     * @return null|User
     */
    private function getUser(): ?User
    {
        $token = $this->tokenStorage->getToken();

        if ($token && $token->getUser() && is_object($token->getUser()) && $token->getUser() instanceof User) {
            return $token->getUser();
        }

        return null;
    }

    /**
     * @param string $locale
     */
    private function changeLocale(string $locale)
    {
        $locale = $this->sanitizeLocale($locale);

        $this->requestStack->getMasterRequest()->setLocale($locale);
        $this->session->set('_locale', $locale);
    }

    /**
     * @param string $locale
     *
     * @return string
     */
    private function sanitizeLocale(string $locale): string
    {
        $locale = strtolower($locale);

        if (strpos($locale, '_')) {
            $locale = substr($locale, 0, strpos($locale, '_'));
        }

        if (!in_array($locale, $this->availableLocales)) {
            $locale = $this->defaultLocale;
        }

        return $locale;
    }
}
