<?php

namespace App\Task;

use Bundles\GoogleTaskBundle\Api\TaskInterface;

abstract class AbstractTask implements TaskInterface
{
    public function getQueueName() : string
    {
        return getenv('GCP_QUEUE_GENERIC');
    }
}