<?php

namespace App\Manager;

use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Message;
use App\Repository\MessageRepository;

class MessageManager
{
    /**
     * @var MessageRepository
     */
    private $messageRepository;

    /**
     * @param MessageRepository $messageRepository
     */
    public function __construct(MessageRepository $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    /**
     * @return string
     */
    public function generateWebCode(): string
    {
        return $this->messageRepository->generateWebCode();
    }

    /**
     * @param int $messageId
     *
     * @return Message|null
     */
    public function find(int $messageId): ?Message
    {
        return $this->messageRepository->find($messageId);
    }

    /**
     * @param Message $message
     * @param Choice  $choice
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function toggleAnswer(Message $message, Choice $choice)
    {
        $this->messageRepository->toggleAnswer($message, $choice);
    }

    /**
     * @param Campaign $campaign
     *
     * @return int
     */
    public function getNumberOfSentMessages(Campaign $campaign) : int
    {
        return $this->messageRepository->getNumberOfSentMessages($campaign);
    }
}