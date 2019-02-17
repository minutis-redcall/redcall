<?php

namespace App\Communication;

use App\Communication\Processor\ProcessorInterface;
use App\Entity\Communication;
use App\Entity\Message;
use App\Issue\IssueLogger;
use App\Repository\ChoiceRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;

class Dispatcher
{
    /** @var ProcessorInterface */
    private $processor;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var IssueLogger */
    private $eventLogger;

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
     * @param IssueLogger            $eventLogger
     * @param ChoiceRepository       $choiceRepository
     * @param MessageRepository      $messageRepository
     */
    public function __construct(ProcessorInterface $processor,
        EntityManagerInterface $entityManager,
        IssueLogger $eventLogger,
        ChoiceRepository $choiceRepository,
        MessageRepository $messageRepository)
    {
        $this->processor         = $processor;
        $this->entityManager     = $entityManager;
        $this->eventLogger       = $eventLogger;
        $this->choiceRepository  = $choiceRepository;
        $this->messageRepository = $messageRepository;
    }

    /**
     * @param Communication $communication
     */
    public function dispatch(Communication $communication)
    {
        if (
            $communication->getStatus() === Communication::STATUS_DISPATCHING
            || $communication->getStatus() === Communication::STATUS_DISPATCHED
        ) {
            return;
        }

        $communication->setStatus(Communication::STATUS_DISPATCHING);

        try {
            $this->processor->process($communication);
            $communication->setStatus(Communication::STATUS_DISPATCHED);
        } catch (\Throwable $e) {
            $this->eventLogger->fileIssueFromException('Failed to dispatch communication', $e, IssueLogger::SEVERITY_CRITICAL, [
                'communication_id'    => $communication->getId(),
                'communication_type'  => $communication->getType(),
                'targeted_volunteers' => $communication->getMessages()->count(),
            ]);

            $communication->setStatus(Communication::STATUS_FAILED);
        }

        $this->entityManager->flush();
    }

    /**
     * @param Message $message
     * @param string  $answer
     *
     * @throws \RuntimeException
     */
    public function processInboundAnswer(Message $message, string $answer)
    {
        if (!$message->getCommunication()->isMultipleAnswer()) {
            $this->messageRepository->addAnswer($message, $answer);
        } else {
            foreach (array_filter(explode(' ', $answer)) as $split) {
                $this->messageRepository->addAnswer($message, $split);
            }
        }
    }
}