<?php

namespace App\Task;

use App\Communication\Sender;
use App\Entity\Communication;
use App\Manager\CommunicationManager;
use App\Queues;
use App\Services\VoiceCalls;
use Bundles\GoogleTaskBundle\Contracts\TaskInterface;
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

    /**
     * @var VoiceCalls
     */
    private $voiceCalls;

    /**
     * @var Sender
     */
    private $sender;

    public function __construct(TaskSender $taskSender,
        CommunicationManager $communicationManager,
        VoiceCalls $voiceCalls,
        Sender $sender)
    {
        $this->taskSender           = $taskSender;
        $this->communicationManager = $communicationManager;
        $this->voiceCalls           = $voiceCalls;
        $this->sender               = $sender;
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

        if (0 === count($communication->getMessages())) {
            return;
        }

        // We need to heat MP3 cache for voice call communications in order to prevent
        // race conditions if several people are hanging in at the same time.
        if (Communication::TYPE_CALL === $communication->getType()) {
            $this->voiceCalls->prepareMedias($communication);
        }

        $this->sender->sendCommunication($communication);

        //        foreach ($communication->getMessages() as $message) {
        //            $this->taskSender->fire($communication->getSendTaskName(), [
        //                'message_id' => $message->getId(),
        //            ]);
        //        }
    }

    public function getQueueName() : string
    {
        return Queues::CREATE_TRIGGER;
    }
}