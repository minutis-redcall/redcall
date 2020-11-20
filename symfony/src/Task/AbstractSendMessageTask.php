<?php

namespace App\Task;

use App\Communication\Sender;
use App\Manager\MessageManager;
use Bundles\GoogleTaskBundle\Api\TaskInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

abstract class AbstractSendMessageTask implements TaskInterface
{
    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var Sender
     */
    private $sender;

    public function __construct(MessageManager $messageManager, Sender $sender)
    {
        $this->messageManager = $messageManager;
        $this->sender         = $sender;
    }

    public function execute(array $context)
    {
        if (!$context['message_id'] ?? false) {
            throw new BadRequestHttpException('No message ID given');
        }

        $message = $this->messageManager->find($context['message_id']);
        if (!$message) {
            throw new BadRequestHttpException('Invalid message ID given');
        }

        $this->sender->sendMessage($message, false);
    }
}