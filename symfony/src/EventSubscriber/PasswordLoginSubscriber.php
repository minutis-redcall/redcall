<?php

namespace App\EventSubscriber;

use App\Entity\UserInformation;
use App\Repository\UserInformationRepository;
use Bundles\PasswordLoginBundle\Event\PasswordLoginEvents;
use Bundles\PasswordLoginBundle\Event\PostEditProfileEvent;
use Bundles\PasswordLoginBundle\Event\PostRegisterEvent;
use Bundles\PasswordLoginBundle\Event\PreEditProfileEvent;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PasswordLoginSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserInformationRepository
     */
    private $userInformationRepository;

    /**
     * @var UserInformation
     */
    private $userInformation;

    /**
     * PasswordLoginSubscriber constructor.
     *
     * @param UserInformationRepository $userPreferenceRepository
     */
    public function __construct(UserInformationRepository $userPreferenceRepository)
    {
        $this->userInformationRepository = $userPreferenceRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            PasswordLoginEvents::POST_REGISTER     => 'onPostRegister',
            PasswordLoginEvents::PRE_EDIT_PROFILE  => 'onPreEditProfile',
            PasswordLoginEvents::POST_EDIT_PROFILE => 'onPostEditProfile',
        ];
    }

    /**
     * @param PostRegisterEvent $event
     */
    public function onPostRegister(PostRegisterEvent $event)
    {
        $preferences = new UserInformation();
        $preferences->setUser($event->getUser());

        $this->userInformationRepository->save($preferences);
    }

    /**
     * @param PreEditProfileEvent $event
     */
    public function onPreEditProfile(PreEditProfileEvent $event)
    {
        $this->userInformation = $this->userInformationRepository->find($event->getUser());
        $this->userInformationRepository->removeForUser($event->getUser());
    }

    /**
     * @param PostEditProfileEvent $event
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function onPostEditProfile(PostEditProfileEvent $event)
    {
        if ($this->userInformation) {
            $this->userInformationRepository->changeLocale($event->getUser(), $this->userInformation->getLocale());
        }
    }
}