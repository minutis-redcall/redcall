<?php

namespace App\EventSubscriber;

use App\Entity\UserPreference;
use App\Repository\UserPreferenceRepository;
use Bundles\PasswordLoginBundle\Event\PasswordLoginEvents;
use Bundles\PasswordLoginBundle\Event\PostEditProfileEvent;
use Bundles\PasswordLoginBundle\Event\PreEditProfileEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PasswordLoginSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserPreferenceRepository
     */
    private $userPreferenceRepository;

    /**
     * @var UserPreference
     */
    private $userPreference;

    /**
     * PasswordLoginSubscriber constructor.
     *
     * @param UserPreferenceRepository $userPreferenceRepository
     */
    public function __construct(UserPreferenceRepository $userPreferenceRepository)
    {
        $this->userPreferenceRepository = $userPreferenceRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            PasswordLoginEvents::PRE_EDIT_PROFILE  => 'onPreEditProfile',
            PasswordLoginEvents::POST_EDIT_PROFILE => 'onPostEditProfile',
        ];
    }

    /**
     * @param PreEditProfileEvent $event
     */
    public function onPreEditProfile(PreEditProfileEvent $event)
    {
        $this->userPreference = $this->userPreferenceRepository->find($event->getUser());
        $this->userPreferenceRepository->removeForUser($event->getUser());
    }

    /**
     * @param PostEditProfileEvent $event
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onPostEditProfile(PostEditProfileEvent $event)
    {
        $this->userPreferenceRepository->changeLocale($event->getUser(), $this->userPreference->getLocale());
    }
}