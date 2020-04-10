<?php

namespace App\EventSubscriber;

use App\Entity\Cost;
use App\Manager\CostManager;
use App\Manager\MessageManager;
use App\Services\VoiceCalls;
use Bundles\TwilioBundle\Entity\TwilioMessage;
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
            TwilioEvents::MESSAGE_PRICE_UPDATED    => 'onPriceUpdated',
            TwilioEvents::MESSAGE_RECEIVED => 'onMessageReceived',
            TwilioEvents::CALL_RECEIVED => 'onCallReceived',
            TwilioEvents::CALL_ESTABLISHED => 'onCallEstablished',
            TwilioEvents::CALL_KEY_PRESSED => 'onCallKeyPressed',
        ];
    }

    /**
     * @param TwilioMessageEvent $event
     */
    public function onPriceUpdated(TwilioMessageEvent $event)
    {
        $twilioMessage = $event->getMessage();

        $message = null;
        if ($messageId = $twilioMessage->getContext()['message_id'] ?? null) {
            $message = $this->messageManager->find($messageId);
        }

        $this->costManager->saveCost(
            TwilioMessage::DIRECTION_INBOUND === $twilioMessage->getDirection() ? Cost::DIRECTION_INBOUND : Cost::DIRECTION_OUTBOUND,
            $twilioMessage->getFromNumber(),
            $twilioMessage->getToNumber(),
            $twilioMessage->getMessage(),
            $twilioMessage->getPrice(),
            $twilioMessage->getUnit(),
            $message
        );
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

    /**
     * @param TwilioCallEvent $event
     */
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
        $call = $event->getCall();

        $message = null;
        if ($messageId = $call->getContext()['message_id'] ?? null) {
            $message = $this->messageManager->find($messageId);
        }

        if (!$message) {
            throw new \LogicException('An outgoing call must be attached to a RedCall message');
        }

        return $this->voiceCalls->establishCall($message);
    }

    public function onCallKeyPressed(TwilioCallEvent $event)
    {
        $key = $event->getKeyPressed();
        if (0 === $key) {
            $this->onCallEstablished($event);

            return;
        }

        $call = $event->getCall();

        $message = null;
        if ($messageId = $call->getContext()['message_id'] ?? null) {
            $message = $this->messageManager->find($messageId);
        }

        if (!$message) {
            throw new \LogicException('An outgoing call must be attached to a RedCall message');
        }

        $answer = sprintf('%s%s', $message->getPrefix(), $key);

        $response = new VoiceResponse();

        $choice = $message->getCommunication()->getChoiceByCode($message->getPrefix(), $answer);
        if (!$choice) {
            // Invalid answer
            $this->say($response, $this->translator->trans('message.call.unknown'));
            $response->pause(['length' => 1]);
            $gather = $response->gather(['numDigits' => 1]);
            $this->say($gather, implode(' ', $this->formatter->formatCallContent($message->getCommunication())));
            $event->setResponse($response);
            return;
        }

        $this->say($response, $this->translator->trans('message.call.answer', [
            '%choice%' => $choice->getLabel(),
        ]));

        $this->messageManager->addAnswer($message, $answer);

        $event->setResponse($response);
    }

    private function say(VoiceResponse $response, string $message)
    {
        $response->say($message, [
            'voice' => 'alice',
            'language' => 'fr-FR',
        ]);
    }
}