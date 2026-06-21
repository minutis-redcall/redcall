<?php

namespace App\EventSubscriber;

use App\Entity\Answer;
use App\Entity\Communication;
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
            $this->onCommunicationChange($entity->getCommunication());
        }

        if ($entity instanceof Answer || $entity instanceof Cost) {
            if ($entity->getMessage()) {
                $this->onCommunicationChange($entity->getMessage()->getCommunication());
            }
        }
    }

    private function onCommunicationChange(Communication $communication)
    {
        $communication->setReport(null);

        // During campaign creation, messages can be flushed before their
        // communication is linked to a campaign. In that transient state there
        // is nothing to track yet.
        $campaign = $communication->getCampaign();
        if ($campaign) {
            $this->trackCampaign($campaign->getId());
        }
    }

    private function trackCampaign(int $campaignId)
    {
        if (!in_array($campaignId, $this->pendingCampaignIds, true)) {
            $this->pendingCampaignIds[] = $campaignId;
        }
    }
}
