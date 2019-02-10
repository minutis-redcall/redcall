<?php

namespace App\Communication;

use App\Entity\Message;
use App\Issue\IssueLogger;
use App\SMS\SMSProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Sender
{
    /** @var SMSProvider */
    private $SMSProvider;

    /** @var Formatter */
    private $formatter;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var IssueLogger */
    private $issueLogger;

    /**
     * Sender constructor.
     *
     * @param SMSProvider            $SMSProvider
     * @param Formatter              $formatter
     * @param EntityManagerInterface $entityManager
     * @param IssueLogger            $issueLogger
     * @param ParameterBagInterface  $parameterBag ;
     */
    public function __construct(
        SMSProvider $SMSProvider,
        Formatter $formatter,
        EntityManagerInterface $entityManager,
        IssueLogger $issueLogger
    ) {
        $this->SMSProvider   = $SMSProvider;
        $this->formatter     = $formatter;
        $this->entityManager = $entityManager;
        $this->issueLogger   = $issueLogger;
    }

    /**
     * @param Message $message
     */
    public function send(Message $message)
    {
        $volunteer = $message->getVolunteer();

        try {
            $SMSSent = $this->SMSProvider->send(
                $this->formatter->formatMessageContent($message),
                $volunteer->getPhoneNumber()
            );

            $message->setMessageId($SMSSent->getId());
            $message->setCost($SMSSent->getCost());
            $message->setSent(true);

            $this->entityManager->merge($message);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->issueLogger->fileIssueFromException('Failed to send SMS to volunteer', $e, IssueLogger::SEVERITY_MAJOR, [
                'volunteer_id'  => $volunteer->getId(),
                'provider_code' => $this->SMSProvider->getProviderCode(),
            ]);
        }
    }
}