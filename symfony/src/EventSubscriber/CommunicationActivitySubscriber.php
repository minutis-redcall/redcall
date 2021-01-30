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
        $this->onChange(
            $args->getObject()
        );
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->onChange(
            $args->getObject()
        );
    }

    public function onChange($entity)
    {
        if ($entity instanceof Message) {
            $entity->getCommunication()->setReport(null);
        }

        if ($entity instanceof Answer || $entity instanceof Cost) {
            if ($entity->getMessage()) {
                $entity->getMessage()->getCommunication()->setReport(null);
            }
        }
    }
}