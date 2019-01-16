<?php

namespace App\Services;

use App\Repository\UserPreferenceRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class Locale
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
     * @var UserPreferenceRepository
     */
    private $userPreferenceRepository;

    /**
     * Locale constructor.
     *
     * @param RequestStack             $requestStack
     * @param SessionInterface         $session
     * @param ParameterBagInterface    $parameterBag
     * @param UserPreferenceRepository $userPreferenceRepository
     * @param TokenStorageInterface    $tokenStorage
     */
    public function __construct(RequestStack $requestStack,
        SessionInterface $session,
        ParameterBagInterface $parameterBag,
        UserPreferenceRepository $userPreferenceRepository,
        TokenStorageInterface $tokenStorage)
    {
        $this->requestStack             = $requestStack;
        $this->session                  = $session;
        $this->defaultLocale            = $parameterBag->get('locale');
        $this->availableLocales         = $parameterBag->get('locale_list');
        $this->userPreferenceRepository = $userPreferenceRepository;
        $this->tokenStorage             = $tokenStorage;
    }

    /**
     * @param string $locale
     */
    public function save(string $locale)
    {
        $this->changeLocale($locale);

        if ($user = $this->getUser()) {
            $this->userPreferenceRepository->changeLocale($user, $locale);
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

    public function restoreFromDatabase()
    {
        // Set locale from user preferences
        if ($this->getUser()) {
            $preferences = $this->userPreferenceRepository->getByUser($this->getUser());
            if ($preferences->getLocale()) {
                $this->changeLocale($preferences->getLocale());
            } else {
                $this->userPreferenceRepository->changeLocale($this->getUser(), $this->requestStack->getMasterRequest()->getLocale());
            }
        }
    }

    /**
     * @return null|UserInterface
     */
    private function getUser(): ?UserInterface
    {
        $token = $this->tokenStorage->getToken();

        if ($token && $token->getUser() && is_object($token->getUser()) && $token->getUser() instanceof UserInterface) {
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
        $locale = \strtolower($locale);

        if (\strpos($locale, '_')) {
            $locale = \substr($locale, 0, \strpos($locale, '_'));
        }

        if (!\in_array($locale, $this->availableLocales)) {
            $locale = $this->defaultLocale;
        }

        return $locale;
    }
}
