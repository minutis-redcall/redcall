<?php

namespace App\Communication;

use App\Entity\Communication;
use App\Entity\Message;
use App\Provider\Call\CallProvider;
use App\Provider\Email\EmailProvider;
use App\Provider\SMS\SMSProvider;
use App\Services\MessageFormatter;
use Doctrine\ORM\EntityManagerInterface;

class Sender
{
    // We should not send too many messages at the same time
    // to prevent Twilio reaching GAE quotas (200 qpm/ip)
    const PAUSE_SMS = 500000; // 2 sms / second
    const PAUSE_CALL = 200000; // 5 calls / second
    const PAUSE_EMAIL = 100000; // 10 emails / second

    /**
     * @var SMSProvider
     */
    private $SMSProvider;

    /**
     * @var CallProvider
     */
    private $callProvider;

    /**
     * @var EmailProvider
     */
    private $emailProvider;

    /**
     * @var MessageFormatter
     */
    private $formatter;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        SMSProvider $SMSProvider,
        CallProvider $callProvider,
        EmailProvider $emailProvider,
        MessageFormatter $formatter,
        EntityManagerInterface $entityManager
    ) {
        $this->SMSProvider   = $SMSProvider;
        $this->callProvider  = $callProvider;
        $this->emailProvider = $emailProvider;
        $this->formatter     = $formatter;
        $this->entityManager = $entityManager;
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
                $this->sendCall($message);
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

        try {
            $messageId = $this->SMSProvider->send(
                $volunteer->getPhoneNumber(),
                $this->formatter->formatSMSContent($message),
                ['message_id' => $message->getId()]
            );

            if ($messageId) {
                $message->setMessageId($messageId);
                $message->setSent(true);
            }
        } catch (\Exception $e) {
            $message->setError($e->getMessage());
        }

        $this->entityManager->merge($message);
        $this->entityManager->flush();
    }

    /**
     * @param Message $message
     */
    public function sendCall(Message $message)
    {
        $volunteer = $message->getVolunteer();

        if (!$volunteer->getPhoneNumber()) {
            return;
        }

        try {
            $messageId = $this->callProvider->send(
                $volunteer->getPhoneNumber(),
                ['message_id' => $message->getId()]
            );

            if ($messageId) {
                $message->setMessageId($messageId);
                $message->setSent(true);
            }
        } catch (\Exception $e) {
            $message->setError($e->getMessage());
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

        try {
            $this->emailProvider->send(
                $message->getVolunteer()->getEmail(),
                $message->getCommunication()->getSubject(),
                $this->formatter->formatTextEmailContent($message),
                $this->formatter->formatHtmlEmailContent($message)
            );

            $message->setMessageId(time());
            $message->setSent(true);
        } catch (\Exception $e) {
            $message->setError($e->getMessage());
        }

        $this->entityManager->merge($message);
        $this->entityManager->flush();
    }
}