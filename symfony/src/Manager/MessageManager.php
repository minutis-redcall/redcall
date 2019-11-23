<?php

namespace App\Manager;

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
}