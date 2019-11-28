<?php

namespace App\Communication;

use App\Communication\Processor\ProcessorInterface;
use App\Entity\Message;
use App\Repository\ChoiceRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;

class Dispatcher
{
    /** @var ProcessorInterface */
    private $processor;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ChoiceRepository */
    private $choiceRepository;

    /**
     * @var MessageRepository
     */
    private $messageRepository;

    /**
     * CommunicationManager constructor.
     *
     * @param ProcessorInterface     $processor
     * @param EntityManagerInterface $entityManager
     * @param ChoiceRepository       $choiceRepository
     * @param MessageRepository      $messageRepository
     */
    public function __construct(ProcessorInterface $processor,
        EntityManagerInterface $entityManager,
        ChoiceRepository $choiceRepository,
        MessageRepository $messageRepository)
    {
        $this->processor         = $processor;
        $this->entityManager     = $entityManager;
        $this->choiceRepository  = $choiceRepository;
        $this->messageRepository = $messageRepository;
    }

    /**
     * @param Message $message
     * @param string  $answer
     *
     * @throws \RuntimeException
     */
    public function processInboundAnswer(Message $message, string $answer)
    {
        $this->messageRepository->addAnswer($message, $answer);
    }
}