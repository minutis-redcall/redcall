<?php

namespace App\Communication;

use App\Entity\Communication;
use App\Entity\Message;
use App\Provider\Email\EmailProvider;
use App\Provider\SMS\SMSProvider;
use App\Services\MessageFormatter;
use Doctrine\ORM\EntityManagerInterface;

class Sender
{
    /**
     * @var SMSProvider
     */
    private $SMSProvider;

    /**
     * @var MessageFormatter
     */
    private $formatter;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EmailProvider
     */
    private $emailProvider;

    /**
     * Sender constructor.
     *
     * @param SMSProvider            $SMSProvider
     * @param MessageFormatter       $formatter
     * @param EntityManagerInterface $entityManager
     * @param EmailProvider          $emailProvider
     */
    public function __construct(
        SMSProvider $SMSProvider,
        MessageFormatter $formatter,
        EntityManagerInterface $entityManager,
        EmailProvider $emailProvider
    ) {
        $this->SMSProvider   = $SMSProvider;
        $this->formatter     = $formatter;
        $this->entityManager = $entityManager;
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

        if (!$volunteer->getPhoneNumber()) {
            return;
        }

        $messageId = $this->SMSProvider->send(
            $volunteer->getPhoneNumber(),
            $this->formatter->formatSMSContent($message),
            ['message_id' => $message->getId()]
        );

        if ($messageId) {
            $message->setMessageId($messageId);
            $message->setSent(true);
        }

        $this->entityManager->merge($message);
        $this->entityManager->flush();
    }

    /**
     * @param Message $message
     */
    public function sendEmail(Message $message)
    {
        if (!$message->getVolunteer()->getEmail()) {
            return;
        }

        $this->emailProvider->send(
            $message->getVolunteer()->getEmail(),
            $message->getCommunication()->getSubject(),
            $this->formatter->formatTextEmailContent($message),
            $this->formatter->formatHtmlEmailContent($message)
        );

        $message->setMessageId(time());
        $message->setSent(true);

        $this->entityManager->merge($message);
        $this->entityManager->flush();
    }
}