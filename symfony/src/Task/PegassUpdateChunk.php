<?php

namespace App\Task;

use App\Queues;
use Bundles\GoogleTaskBundle\Contracts\TaskInterface;
use Bundles\GoogleTaskBundle\Service\TaskSender;

class PegassUpdateChunk implements TaskInterface
{
    /**
     * @var TaskSender
     */
    private $async;

    public function __construct(TaskSender $async)
    {
        $this->async = $async;
    }

    public function execute(array $context)
    {
        foreach ($context['chunk'] as $identifier => $data) {
            $this->async->fire(PegassUpdateOneEntity::class, [
                'type'       => $context['type'],
                'identifier' => $identifier,
                'data'       => $data,
            ]);
        }
    }

    public function getQueueName() : string
    {
        return Queues::PEGASS_UPDATE_CHUNK;
    }
}