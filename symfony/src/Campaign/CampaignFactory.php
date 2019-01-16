<?php

namespace App\Campaign;

use App\Entity\Campaign;
use App\Entity\Communication;

class CampaignFactory
{
    /**
     * @param string        $label
     * @param string        $type
     * @param Communication $initialCommunication
     *
     * @return Campaign
     */
    public function create(string $label, string $type, Communication $initialCommunication): Campaign
    {
        $createdAt = new \DateTime();
        $campaign = new Campaign();
        $campaign
            ->setLabel($label)
            ->setType($type)
            ->setActive(true)
            ->setCreatedAt($createdAt)
            ->addCommunication($initialCommunication)
        ;

        return $campaign;
    }
}