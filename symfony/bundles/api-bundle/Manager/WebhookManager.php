<?php

namespace Bundles\ApiBundle\Manager;

use Bundles\ApiBundle\Repository\WebhookRepository;

class WebhookManager
{
    /**
     * @var WebhookRepository
     */
    private $webhookRepository;

    public function __construct(WebhookRepository $webhookRepository)
    {
        $this->webhookRepository = $webhookRepository;
    }
}