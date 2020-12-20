<?php

namespace App\Task;

use App\Manager\CommunicationManager;
use App\Queues;
use Bundles\GoogleTaskBundle\Api\TaskInterface;
use Bundles\GoogleTaskBundle\Service\TaskSender;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SendCommunicationTask implements TaskInterface
{
    /**
     * @var TaskSender
     */
    private $taskSender;

    /**
     * @var CommunicationManager
     */
    private $communicationManager;

    public function __construct(TaskSender $taskSender, CommunicationManager $communicationManager)
    {
        $this->taskSender           = $taskSender;
        $this->communicationManager = $communicationManager;
    }

    public function execute(array $context)
    {
        if (!$context['communication_id'] ?? false) {
            throw new BadRequestHttpException('No communication ID given');
        }

        $communication = $this->communicationManager->find($context['communication_id']);
        if (!$communication) {
            throw new BadRequestHttpException('Invalid communication ID given');
        }

        foreach ($communication->getMessages() as $message) {
            $this->taskSender->fire($communication->getSendTaskName(), [
                'message_id' => $message->getId(),
            ]);
        }
    }

    public function getQueueName() : string
    {
        return Queues::CREATE_TRIGGER;
    }
}