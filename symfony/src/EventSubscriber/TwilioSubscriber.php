<?php

namespace App\EventSubscriber;

use App\Manager\MessageManager;
use Bundles\TwilioBundle\Event\TwilioEvent;
use Bundles\TwilioBundle\TwiliEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TwilioSubscriber implements EventSubscriberInterface
{
    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @param MessageManager $messageManager
     */
    public function __construct(MessageManager $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            TwiliEvents::PRICE_UPDATED    => 'onPriceUpdated',
            TwiliEvents::MESSAGE_RECEIVED => 'onMessageReceived',
        ];
    }

    /**
     * @param TwilioEvent $event
     */
    public function onPriceUpdated(TwilioEvent $event)
    {
        $twilioMessage = $event->getMessage();

        $messageId = $twilioMessage->getContext()['message_id'] ?? null;
        if (!$messageId) {
            return;
        }

        $message = $this->messageManager->find($messageId);
        if (!$message) {
            return;
        }

        $message->setCost(-1 * (float)$twilioMessage->getPrice());
        $message->setCurrency($twilioMessage->getUnit());

        $this->messageManager->save($message);
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