<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

class TimezoneSubscriber implements EventSubscriberInterface
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', -512]],
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$this->security->getUser()) {
            date_default_timezone_set('Europe/Paris');
        }

        /** @var User $me */
        $me = $this->security->getUser();

        date_default_timezone_set(
            $me->getTimezone()
        );
    }
}