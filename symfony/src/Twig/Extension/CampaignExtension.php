<?php

namespace App\Twig\Extension;

use App\Entity\Campaign;

class CampaignExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('status_badge', [$this, 'getStatusBadge']),
        ];
    }

    /**
     * @param Campaign $campaign
     *
     * @return string
     */
    public function getStatusBadge(Campaign $campaign)
    {
        return $campaign->isActive() ? 'info' : 'dark';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'campaign';
    }
}
