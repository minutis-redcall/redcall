<?php

namespace App\EventSubscriber;

use App\Manager\RefreshManager;
use Bundles\PegassCrawlerBundle\Event\PegassEvent;
use Bundles\PegassCrawlerBundle\PegassEvents;
use Doctrine\ORM\NonUniqueResultException;
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
            PegassEvents::UPDATE_DEPARTMENT => 'onUpdateDepartment',
            PegassEvents::UPDATE_STRUCTURE  => 'onUpdateStructure',
            PegassEvents::UPDATE_VOLUNTEER  => 'onUpdateVolunteer',
        ];
    }

    /**
     * A structure may have been added or removed in the department,
     * thus we loop through all structures to check their update date
     * on the Pegass information.
     *
     * @param PegassEvent $event
     */
    public function onUpdateDepartment(PegassEvent $event)
    {
        $this->refreshManager->refreshStructures(false);
    }

    /**
     * @param PegassEvent $event
     *
     * @throws NonUniqueResultException
     */
    public function onUpdateStructure(PegassEvent $event)
    {
        $this->refreshManager->refreshStructure($event->getPegass(), false);
    }

    /**
     * @param PegassEvent $event
     */
    public function onUpdateVolunteer(PegassEvent $event)
    {
        $this->refreshManager->refreshVolunteer($event->getPegass(), false);
    }
}