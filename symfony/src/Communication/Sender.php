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
    const PAUSE_SMS = 100000; // 10 sms / second
    const PAUSE_CALL = 100000; // 10 calls / second
    const PAUSE_EMAIL = 300000; // 3 email / second

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
     * @param Communication $communication
     * @param bool          $force
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function sendCommunication(Communication $communication, bool $force = false)
    {
        foreach ($communication->getMessages() as $message) {
            if ($force || $message->canBeSent()) {
                $this->sendMessage($message);
            }
        }
    }

    /**
     * @param Message $message
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function sendMessage(Message $message)
    {
        switch ($message->getCommunication()->getType()) {
            case Communication::TYPE_SMS:
                $this->sendSms($message);
                usleep(self::PAUSE_SMS);
                break;
            case Communication::TYPE_CALL:
                usleep(self::PAUSE_CALL);
                break;
            case Communication::TYPE_EMAIL:
                $this->sendEmail($message);
                usleep(self::PAUSE_EMAIL);
                break;
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
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
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