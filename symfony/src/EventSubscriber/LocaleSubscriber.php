<?php

namespace App\EventSubscriber;

use App\Manager\LocaleManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private $locale;

    public function __construct(LocaleManager $locale)
    {
        $this->locale = $locale;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST             => [['onKernelRequest', 16]],
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
        ];
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->locale->restoreFromSession();
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $this->locale->restoreFromUser();
    }
}
