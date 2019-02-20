<?php

namespace App\Communication;

use App\Email\EmailProvider;
use App\Entity\Communication;
use App\Entity\Message;
use App\Issue\IssueLogger;
use App\SMS\SMSProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Sender
{
    /**
     * @var SMSProvider
     */
    private $SMSProvider;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IssueLogger
     */
    private $issueLogger;

    /**
     * @var EmailProvider
     */
    private $emailProvider;

    /**
     * Sender constructor.
     *
     * @param SMSProvider            $SMSProvider
     * @param Formatter              $formatter
     * @param EntityManagerInterface $entityManager
     * @param IssueLogger            $issueLogger
     * @param ParameterBagInterface  $parameterBag
     * @param EmailProvider          $emailProvider
     */
    public function __construct(
        SMSProvider $SMSProvider,
        Formatter $formatter,
        EntityManagerInterface $entityManager,
        IssueLogger $issueLogger,
        EmailProvider $emailProvider
    ) {
        $this->SMSProvider   = $SMSProvider;
        $this->formatter     = $formatter;
        $this->entityManager = $entityManager;
        $this->issueLogger   = $issueLogger;
        $this->emailProvider = $emailProvider;
    }

    /**
     * @param Message $message
     */
    public function send(Message $message)
    {
        if ($message->getCommunication()->getType() === Communication::TYPE_SMS) {
            $this->sendSms($message);
        } else {
            $this->sendEmail($message);
        }
    }

    /**
     * @param Message $message
     */
    public function sendSms(Message $message)
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

    /**
     * @param Message $message
     */
    public function sendEmail(Message $message)
    {
        if (!$message->getVolunteer()->getEmail()) {
            return;
        }

        try {
            $this->emailProvider->send(
                $message->getVolunteer()->getEmail(),
                $message->getCommunication()->getSubject(),
                $this->formatter->formatMessageContent($message)
            );

            $message->setMessageId(time());
            $message->setCost(0);
            $message->setSent(true);

            $this->entityManager->merge($message);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->issueLogger->fileIssueFromException('Failed to send SMS to volunteer', $e, IssueLogger::SEVERITY_MAJOR, [
                'volunteer_id' => $message->getVolunteer()->getId(),
            ]);
        }
    }
}