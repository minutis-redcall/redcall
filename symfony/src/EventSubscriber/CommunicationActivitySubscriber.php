<?php

namespace App\EventSubscriber;

use App\Entity\Answer;
use App\Entity\Cost;
use App\Entity\Message;
use Doctrine\ORM\Event\LifecycleEventArgs;

class CommunicationActivitySubscriber
{
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof Message) {
            $entity->getCommunication()->onChange();
        }

        if ($entity instanceof Answer || $entity instanceof Cost) {
            $entity->getMessage()->getCommunication()->onChange();
        }
    }
}