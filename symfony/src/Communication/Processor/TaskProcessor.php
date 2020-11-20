<?php

namespace App\Communication\Processor;

use App\Entity\Communication;
use App\Task\SendCommunicationTask;
use Bundles\GoogleTaskBundle\Service\TaskSender;

class TaskProcessor implements ProcessorInterface
{
    /**
     * @var TaskSender
     */
    private $async;

    public function __construct(TaskSender $async)
    {
        $this->async = $async;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Communication $communication)
    {
        $this->async->fire(SendCommunicationTask::class, [
            'communication_id' => $communication->getId(),
        ]);
    }
}