<?php

namespace App\EventSubscriber;

use App\Entity\Cost;
use App\Manager\CostManager;
use App\Manager\MessageManager;
use Bundles\TwilioBundle\Entity\TwilioMessage;
use Bundles\TwilioBundle\Event\TwilioEvent;
use Bundles\TwilioBundle\TwilioEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
            TwilioEvents::PRICE_UPDATED    => 'onPriceUpdated',
            TwilioEvents::MESSAGE_RECEIVED => 'onMessageReceived',
        ];
    }

    /**
     * @param TwilioEvent $event
     */
    public function onPriceUpdated(TwilioEvent $event)
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
     * @param TwilioEvent $event
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onMessageReceived(TwilioEvent $event)
    {
        $twilioMessage = $event->getMessage();

        $messageId = $this->messageManager->handleAnswer($twilioMessage->getFromNumber(), $twilioMessage->getMessage());
        if ($messageId) {
            $twilioMessage->setContext(['message_id' => $messageId]);
        }
    }
}