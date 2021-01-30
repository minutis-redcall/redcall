<?php

namespace App\Communication;

use App\Entity\Communication;
use App\Entity\Message;
use App\Manager\MessageManager;
use App\Provider\Call\CallProvider;
use App\Provider\Email\EmailProvider;
use App\Provider\SMS\SMSProvider;
use App\Services\MessageFormatter;
use App\Tools\PhoneNumber;

class Sender
{
    // We should not send too many messages at the same time
    // to prevent Twilio reaching GAE quotas (200 qpm/ip)
    const PAUSE_SMS   = 500000; // 2 sms / second
    const PAUSE_CALL  = 200000; // 5 calls / second
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
     * @var MessageManager
     */
    private $messageManager;

    public function __construct(SMSProvider $SMSProvider,
        CallProvider $callProvider,
        EmailProvider $emailProvider,
        MessageFormatter $formatter,
        MessageManager $messageManager)
    {
        $this->SMSProvider    = $SMSProvider;
        $this->callProvider   = $callProvider;
        $this->emailProvider  = $emailProvider;
        $this->formatter      = $formatter;
        $this->messageManager = $messageManager;
    }

    public function sendCommunication(Communication $communication, bool $force = false)
    {
        foreach ($communication->getMessages() as $message) {
            if ($force) {
                $message->setSent(false);
                $message->setError(null);
            }
            $this->sendMessage($message);
        }
    }

    public function sendMessage(Message $message, bool $sleep = true)
    {
        if ($this->isMessageNotTransmittable($message)) {
            return;
        }

        switch ($message->getCommunication()->getType()) {
            case Communication::TYPE_SMS:
                $this->sendSms($message);
                if ($sleep) {
                    usleep(self::PAUSE_SMS);
                }
                break;
            case Communication::TYPE_CALL:
                $this->sendCall($message);
                if ($sleep) {
                    usleep(self::PAUSE_CALL);
                }
                break;
            case Communication::TYPE_EMAIL:
                $this->sendEmail($message);
                if ($sleep) {
                    usleep(self::PAUSE_EMAIL);
                }
                break;
        }
    }

    /**
     * @param Message $message
     */
    public function sendSms(Message $message)
    {
        if (!$message->canBeSent()) {
            return;
        }

        $volunteer = $message->getVolunteer();
        $sender    = PhoneNumber::getSmsSender($volunteer->getPhone());

        try {
            $messageId = $this->SMSProvider->send(
                $sender,
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

        $this->messageManager->save($message);
    }

    public function sendCall(Message $message)
    {
        if (!$message->canBeSent()) {
            return;
        }

        $volunteer = $message->getVolunteer();
        $sender    = PhoneNumber::getCallSender($volunteer->getPhone());

        try {
            $messageId = $this->callProvider->send(
                $sender,
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

        $this->messageManager->save($message);
    }

    public function sendEmail(Message $message)
    {
        if (!$message->canBeSent()) {
            return;
        }

        $volunteer = $message->getVolunteer();

        try {
            $this->emailProvider->send(
                $volunteer->getEmail(),
                $message->getCommunication()->getSubject(),
                $this->formatter->formatTextEmailContent($message),
                $this->formatter->formatHtmlEmailContent($message)
            );

            $message->setMessageId(time());
            $message->setSent(true);
        } catch (\Exception $e) {
            $message->setError($e->getMessage());
        }

        $this->messageManager->save($message);
    }

    private function isMessageNotTransmittable(Message $message) : bool
    {
        $error     = null;
        $volunteer = $message->getVolunteer();

        switch ($message->getCommunication()->getType()) {
            case Communication::TYPE_SMS:
                if ($volunteer->getPhoneNumber() && !$volunteer->getPhone()->isMobile()) {
                    $error = 'campaign_status.warning.no_phone_mobile';
                    break;
                }
            // No break here as next checks also work for SMSs
            case Communication::TYPE_CALL:
                if (null === $volunteer->getPhoneNumber()) {
                    $error = 'campaign_status.warning.no_phone';
                    break;
                }
                if (!$volunteer->isPhoneNumberOptin()) {
                    $error = 'campaign_status.warning.no_phone_optin';
                    break;
                }
                break;
            case Communication::TYPE_EMAIL:
                if (null === $volunteer->getEmail()) {
                    $error = 'campaign_status.warning.no_email';
                    break;
                }
                if (!$volunteer->isEmailOptin()) {
                    $error = 'campaign_status.warning.no_email_optin';
                    break;
                }
                break;
        }

        if (null !== $error) {
            $message->setError($error);
            $this->messageManager->save($message);
        }

        return null !== $error;
    }
}