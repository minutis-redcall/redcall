<?php

namespace App\Communication;

use App\Entity\Message;
use App\Repository\MessageRepository;

class Dispatcher
{
    /**
     * @var MessageRepository
     */
    private $messageRepository;

    /**
     * @param MessageRepository $messageRepository
     */
    public function __construct(
        MessageRepository $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    /**
     * @param Message $message
     * @param string  $answer
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function processInboundAnswer(Message $message, string $answer)
    {
        $this->messageRepository->addAnswer($message, $answer);
    }
}