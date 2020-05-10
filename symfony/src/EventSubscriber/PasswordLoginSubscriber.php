<?php

namespace App\EventSubscriber;

use App\Entity\UserInformation;
use App\Manager\UserInformationManager;
use Bundles\PasswordLoginBundle\Event\PasswordLoginEvents;
use Bundles\PasswordLoginBundle\Event\PostEditProfileEvent;
use Bundles\PasswordLoginBundle\Event\PostRegisterEvent;
use Bundles\PasswordLoginBundle\Event\PreEditProfileEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class PasswordLoginSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var UserInformation
     */
    private $userInformation;

    public function __construct(UserInformationManager $userInformationManager, TokenStorageInterface $tokenStorage)
    {
        $this->userInformationManager = $userInformationManager;
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSubscribedEvents()
    {
        return [
            PasswordLoginEvents::POST_REGISTER     => 'onPostRegister',
            PasswordLoginEvents::PRE_EDIT_PROFILE  => 'onPreEditProfile',
            PasswordLoginEvents::POST_EDIT_PROFILE => 'onPostEditProfile',
            AuthenticationEvents::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    public function onPostRegister(PostRegisterEvent $event)
    {
        $preferences = new UserInformation();
        $preferences->setUser($event->getUser());

        $this->userInformationManager->save($preferences);
    }

    public function onPreEditProfile(PreEditProfileEvent $event)
    {
        $this->userInformation = $this->userInformationManager->findOneByUser($event->getUser());
        $this->userInformationManager->removeForUser($event->getUser());
    }

    public function onPostEditProfile(PostEditProfileEvent $event)
    {
        if ($this->userInformation) {
            $this->userInformationManager->changeLocale($event->getUser(), $this->userInformation->getLocale());
        }
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event)
    {
        $token = $event->getAuthenticationToken();
        if (!$token || !$token->getUser() || !($token->getUser() instanceof UserInterface)) {
            return;
        }

        $user = $this->userInformationManager->findOneByUser(
            $token->getUser()
        );

        if ($user->isDeveloper()) {
            $roles = $token->getRoleNames();
            $roles[] = 'ROLE_DEVELOPER';

            $this->updatePrivateProperty($user, 'roles', $roles);
            $this->updatePrivateProperty($token, 'roles', $roles);
        }
    }

    private function updatePrivateProperty($object, string $property, $value)
    {
        // A cleaner way to do this would be to replace the entity in
        // the user_provider (symfony.yaml) by another user that would extend
        // the base user entity.

    }
}