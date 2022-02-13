<?php

namespace App\EventSubscriber;

use App\Event\PegassEvent;
use App\Manager\RefreshManager;
use App\PegassEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PegassSubscriber implements EventSubscriberInterface
{
    private $refreshManager;

    public function __construct(RefreshManager $refreshManager)
    {
        $this->refreshManager = $refreshManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            PegassEvents::UPDATE_STRUCTURE => 'onUpdateStructure',
            PegassEvents::UPDATE_VOLUNTEER => 'onUpdateVolunteer',
        ];
    }

    public function onUpdateStructure(PegassEvent $event)
    {
        $this->refreshManager->refreshStructure($event->getPegass(), false);
    }

    public function onUpdateVolunteer(PegassEvent $event)
    {
        $this->refreshManager->refreshVolunteer($event->getPegass(), false);
    }
}