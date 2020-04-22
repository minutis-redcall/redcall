<?php

namespace App\EventSubscriber;

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
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param CostManager         $costManager
     * @param MessageManager      $messageManager
     * @param VoiceCalls          $voiceCalls
     * @param TranslatorInterface $translator
     */
    public function __construct(CostManager $costManager, MessageManager $messageManager, VoiceCalls $voiceCalls, TranslatorInterface $translator)
    {
        $this->costManager = $costManager;
        $this->messageManager = $messageManager;
        $this->voiceCalls = $voiceCalls;
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            TwilioEvents::MESSAGE_PRICE_UPDATED    => 'onMessagePriceUpdated',
            TwilioEvents::MESSAGE_RECEIVED => 'onMessageReceived',
            TwilioEvents::MESSAGE_ERROR => 'onMessageError',
            TwilioEvents::CALL_PRICE_UPDATED    => 'onCallPriceUpdated',
            TwilioEvents::CALL_RECEIVED => 'onCallReceived',
            TwilioEvents::CALL_ESTABLISHED => 'onCallEstablished',
            TwilioEvents::CALL_KEY_PRESSED => 'onCallKeyPressed',
            TwilioEvents::CALL_ERROR => 'onCallError',
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
        $response = new VoiceResponse();

        $response->say(
            sprintf('%s bonjour, ce numéro ne prend pas d\'appels, merci de contacter votre unité locale afin de poser vos questions.', getenv('BRAND')), [
                'voice' => 'alice',
                'language' => 'fr-FR',
            ]
        );

        $response->pause(['length' => 1]);

        $response->say(
            sprintf('%s greetings, this phone number does not take any calls, please contact your local unit if you have any questions.', getenv('BRAND')), [
                'voice' => 'alice',
                'language' => 'en-GB',
            ]
        );

        $event->setResponse($response);
    }

    public function onCallEstablished(TwilioCallEvent $event)
    {
        $message = $this->getMessageFromCall($event);

        $event->setResponse(
            $this->voiceCalls->establishCall($message)
        );
    }

    public function onCallKeyPressed(TwilioCallEvent $event)
    {
        $message = $this->getMessageFromCall($event);

        $event->setResponse(
            $this->voiceCalls->handleKeyPress($message, $event->getKeyPressed())
        );
    }

    public function onCallError(TwilioCallEvent $event)
    {
        $message = $this->getMessageFromCall($event);

        $message->setError($event->getCall()->getError());

        $this->messageManager->save($message);
    }

    private function getMessageFromSms(TwilioMessageEvent $event): ?Message
    {
        $twilioMessage = $event->getMessage();

        $message = null;
        if ($messageId = $twilioMessage->getContext()['message_id'] ?? null) {
            $message = $this->messageManager->find($messageId);
        }

        return $message;
    }

    private function getMessageFromCall(TwilioCallEvent $event): Message
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