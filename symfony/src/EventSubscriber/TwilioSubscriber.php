<?php

namespace App\EventSubscriber;

use App\Entity\Cost;
use App\Manager\CostManager;
use App\Manager\MessageManager;
use Bundles\TwilioBundle\Entity\TwilioMessage;
use Bundles\TwilioBundle\Event\TwilioCallEvent;
use Bundles\TwilioBundle\Event\TwilioMessageEvent;
use Bundles\TwilioBundle\TwilioEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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
     * @param CostManager    $costManager
     * @param MessageManager $messageManager
     */
    public function __construct(CostManager $costManager, MessageManager $messageManager)
    {
        $this->costManager = $costManager;
        $this->messageManager = $messageManager;
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
}