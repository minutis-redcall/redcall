<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Manager\UserManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * user_structure is a materialized snapshot of each user's triggering scope:
 * assigning a parent structure copies its whole sub-tree at that moment, so
 * sub-structures created or re-parented later drift out of the snapshot. This
 * subscriber heals it on every successful login (all authenticators dispatch
 * LoginSuccessEvent) by materializing missing enabled descendants.
 */
class StructureScopeSubscriber implements EventSubscriberInterface
{
    /**
     * @var UserManager
     */
    private $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public static function getSubscribedEvents() : array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event)
    {
        $user = $event->getUser();

        if ($user instanceof User) {
            $this->userManager->syncStructureScope($user);
        }
    }
}
