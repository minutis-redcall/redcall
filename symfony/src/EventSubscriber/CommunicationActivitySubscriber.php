<?php

namespace App\EventSubscriber;

use App\Entity\Answer;
use App\Entity\Cost;
use App\Entity\Message;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;

class CommunicationActivitySubscriber
{
    /**
     * @var int[]
     */
    private $pendingCampaignIds = [];

    public function postPersist(PostPersistEventArgs $args)
    {
        $this->onChange(
            $args->getObject()
        );
    }

    public function postUpdate(PostUpdateEventArgs $args)
    {
        $this->onChange(
            $args->getObject()
        );
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        if (empty($this->pendingCampaignIds)) {
            return;
        }

        $ids = $this->pendingCampaignIds;
        $this->pendingCampaignIds = [];

        $em = $args->getObjectManager();
        $em->createQuery(
            'UPDATE App\Entity\Campaign c SET c.lastActivityAt = :now WHERE c.id IN (:ids)'
        )
            ->setParameter('now', new \DateTime())
            ->setParameter('ids', $ids)
            ->execute();
    }

    public function onChange($entity)
    {
        if ($entity instanceof Message) {
            $entity->getCommunication()->setReport(null);
            $this->trackCampaign($entity->getCommunication()->getCampaign()->getId());
        }

        if ($entity instanceof Answer || $entity instanceof Cost) {
            if ($entity->getMessage()) {
                $entity->getMessage()->getCommunication()->setReport(null);
                $this->trackCampaign($entity->getMessage()->getCommunication()->getCampaign()->getId());
            }
        }
    }

    private function trackCampaign(int $campaignId)
    {
        if (!in_array($campaignId, $this->pendingCampaignIds, true)) {
            $this->pendingCampaignIds[] = $campaignId;
        }
    }
}
