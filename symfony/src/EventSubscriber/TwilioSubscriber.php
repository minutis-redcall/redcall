<?php

namespace App\EventSubscriber;

use App\Communication\Sender;
use App\Entity\Communication;
use App\Entity\Message;
use App\Manager\CostManager;
use App\Manager\MessageManager;
use App\Services\VoiceCalls;
use Bundles\TwilioBundle\Event\TwilioCallEvent;
use Bundles\TwilioBundle\Event\TwilioMessageEvent;
use Bundles\TwilioBundle\TwilioEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twilio\TwiML\VoiceResponse;

class TwilioSubscriber implements EventSubscriberInterface
{
    /**
     * @var CostManager
     */
    private $costManager;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var VoiceCalls
     */
    private $voiceCalls;

    /**
     * @var Sender
     */
    private $sender;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(CostManager $costManager,
        MessageManager $messageManager,
        VoiceCalls $voiceCalls,
        Sender $sender,
        TranslatorInterface $translator)
    {
        $this->costManager    = $costManager;
        $this->messageManager = $messageManager;
        $this->voiceCalls     = $voiceCalls;
        $this->sender         = $sender;
        $this->translator     = $translator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            TwilioEvents::MESSAGE_PRICE_UPDATED  => 'onMessagePriceUpdated',
            TwilioEvents::MESSAGE_RECEIVED       => 'onMessageReceived',
            TwilioEvents::MESSAGE_ERROR          => 'onMessageError',
            TwilioEvents::CALL_PRICE_UPDATED     => 'onCallPriceUpdated',
            TwilioEvents::CALL_RECEIVED          => 'onCallReceived',
            TwilioEvents::CALL_ESTABLISHED       => 'onCallEstablished',
            TwilioEvents::CALL_KEY_PRESSED       => 'onCallKeyPressed',
            TwilioEvents::CALL_ERROR             => 'onCallError',
            TwilioEvents::CALL_ANSWERING_MACHINE => 'onAnsweringMachine',
        ];
    }

    /**
     * @param TwilioMessageEvent $event
     */
    public function onMessagePriceUpdated(TwilioMessageEvent $event)
    {
        $message = $this->getMessageFromSms($event);

        $this->costManager->saveMessageCost($event->getMessage(), $message);
    }

    /**
     * @param TwilioMessageEvent $event
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onMessageReceived(TwilioMessageEvent $event)
    {
        $twilioMessage = $event->getMessage();

        $messageId = $this->messageManager->handleAnswer($twilioMessage->getFromNumber(), $twilioMessage->getMessage());
        if ($messageId) {
            $twilioMessage->setContext(['message_id' => $messageId]);
        }
    }

    public function onMessageError(TwilioMessageEvent $event)
    {
        $message = $this->getMessageFromSms($event);

        if (!$message) {
            return;
        }

        $message->setError($event->getMessage()->getError());

        $this->messageManager->save($message);
    }

    /**
     * @param TwilioCallEvent $event
     */
    public function onCallPriceUpdated(TwilioCallEvent $event)
    {
        $twilioCall = $event->getCall();

        $message = null;
        if ($messageId = $twilioCall->getContext()['message_id'] ?? null) {
            $message = $this->messageManager->find($messageId);
        }

        $this->costManager->saveCallCost($twilioCall, $message);
    }

    public function onCallReceived(TwilioCallEvent $event)
    {
        $message = $this->messageManager->getMessageFromPhoneNumber(
            $event->getCall()->getFromNumber()
        );

        if ($message && Communication::TYPE_EMAIL !== $message->getCommunication()->getType()) {
            $event->getCall()->setContext([
                'message_id' => $message->getId(),
            ]);

            $event->setResponse(
                $this->voiceCalls->establishCall($event->getCall()->getUuid(), $message)
            );

            return;
        }

        $response = new VoiceResponse();

        $response->say('Votre numéro de téléphone n\'est sur aucun déclenchement actif pour le moment.', [
            'voice'    => 'alice',
            'language' => 'fr-FR',
        ]);

        $response->pause(['length' => 1]);

        $response->say('Your phone number is not currently on any active triggers.', [
            'voice'    => 'alice',
            'language' => 'en-GB',
        ]);

        $event->setResponse($response);
    }

    public function onCallEstablished(TwilioCallEvent $event)
    {
        $message = $this->getMessageFromCall($event);

        $event->setResponse(
            $this->voiceCalls->establishCall($event->getCall()->getUuid(), $message)
        );
    }

    public function onCallKeyPressed(TwilioCallEvent $event)
    {
        $message = $this->getMessageFromCall($event);

        $event->setResponse(
            $this->voiceCalls->handleKeyPress($event->getCall()->getUuid(), $message, $event->getKeyPressed())
        );
    }

    public function onCallError(TwilioCallEvent $event)
    {
        $message = $this->getMessageFromCall($event);

        $message->setError($event->getCall()->getError());

        $this->messageManager->save($message);
    }

    public function onAnsweringMachine(TwilioCallEvent $event)
    {
        $message = $this->getMessageFromCall($event);

        $message->setSent(false);

        $this->sender->sendSms($message);
    }

    private function getMessageFromSms(TwilioMessageEvent $event) : ?Message
    {
        $twilioMessage = $event->getMessage();

        $message = null;
        if ($messageId = $twilioMessage->getContext()['message_id'] ?? null) {
            $message = $this->messageManager->find($messageId);
        }

        return $message;
    }

    private function getMessageFromCall(TwilioCallEvent $event) : Message
    {
        $call = $event->getCall();

        $message = null;
        if ($messageId = $call->getContext()['message_id'] ?? null) {
            $message = $this->messageManager->find($messageId);
        }

        if (!$message) {
            throw new \LogicException('An outgoing call must be attached to a RedCall message');
        }

        return $message;
    }
}