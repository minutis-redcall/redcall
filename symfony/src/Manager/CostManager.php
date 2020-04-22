<?php

namespace App\Manager;

use App\Entity\Cost;
use App\Entity\Message;
use App\Repository\CostRepository;
use Bundles\TwilioBundle\Entity\TwilioCall;
use Bundles\TwilioBundle\Entity\TwilioMessage;
use Bundles\TwilioBundle\Manager\TwilioCallManager;
use Bundles\TwilioBundle\Manager\TwilioMessageManager;

class CostManager
{
    /**
     * @var CostRepository
     */
    private $costRepository;

    /**
     * @var MessageManager
     */
    private $messageManager;

    /**
     * @var TwilioMessageManager
     */
    private $twilioMessageManager;

    /**
     * @var TwilioCallManager
     */
    private $twilioCallManager;

    /**
     * @param CostRepository       $costRepository
     * @param MessageManager       $messageManager
     * @param TwilioMessageManager $twilioMessageManager
     * @param TwilioCallManager    $twilioCallManager
     */
    public function __construct(CostRepository $costRepository, MessageManager $messageManager, TwilioMessageManager $twilioMessageManager, TwilioCallManager $twilioCallManager)
    {
        $this->costRepository = $costRepository;
        $this->messageManager = $messageManager;
        $this->twilioMessageManager = $twilioMessageManager;
        $this->twilioCallManager = $twilioCallManager;
    }

    public function saveMessageCost(TwilioMessage $twilioMessage, Message $message = null)
    {
        $this->saveCost(
            TwilioMessage::DIRECTION_INBOUND === $twilioMessage->getDirection() ? Cost::DIRECTION_INBOUND : Cost::DIRECTION_OUTBOUND,
            $twilioMessage->getFromNumber(),
            $twilioMessage->getToNumber(),
            $twilioMessage->getMessage(),
            $twilioMessage->getPrice(),
            $twilioMessage->getUnit(),
            $message
        );
    }

    public function saveCallCost(TwilioCall $twilioCall, Message $message = null)
    {
        $this->saveCost(
            TwilioCall::DIRECTION_INBOUND === $twilioCall->getDirection() ? Cost::DIRECTION_INBOUND : Cost::DIRECTION_OUTBOUND,
            $twilioCall->getFromNumber(),
            $twilioCall->getToNumber(),
            $twilioCall->getMessage() ?? '',
            $twilioCall->getPrice(),
            $twilioCall->getUnit(),
            $message
        );
    }

    public function recoverCosts()
    {
        $this->costRepository->truncate();
        $this->recoverMessageCosts();
        $this->recoverCallCosts();
    }

    private function saveCost(string $direction, string $fromNumber, string $toNumber, string $body, string $price, string $currency, Message $message = null)
    {
        $cost = new Cost();
        $cost->setDirection($direction);
        $cost->setFromNumber($fromNumber);
        $cost->setToNumber($toNumber);
        $cost->setBody($body);
        $cost->setPrice($price);
        $cost->setCurrency($currency);

        if ($message) {
            $cost->setCreatedAt($message->getUpdatedAt());
            $message->addCost($cost);
        }

        $this->costRepository->save($cost);

        if ($message) {
            $this->messageManager->save($message);
        }
    }

    private function recoverMessageCosts()
    {
        $this->twilioMessageManager->foreach(function(TwilioMessage $twilioMessage) {
            if (!$twilioMessage->getPrice()) {
                return;
            }

            $messageId = $twilioMessage->getContext()['message_id'] ?? 0;
            if (!$messageId) {
                return;
            }

            $message = $this->messageManager->find($messageId);
            if (!$message) {
                return;
            }

            echo sprintf("[sms] Recovered price for message #%d: %s %s\n", $message->getId(), $twilioMessage->getPrice(), $twilioMessage->getUnit());

            $this->saveMessageCost($twilioMessage, $message);
        });
    }

    private function recoverCallCosts()
    {
        $this->twilioCallManager->foreach(function(TwilioCall $twilioCall) {
            if (!$twilioCall->getPrice()) {
                return;
            }

            $messageId = $twilioCall->getContext()['message_id'] ?? 0;
            if (!$messageId) {
                return;
            }

            $message = $this->messageManager->find($messageId);
            if (!$message) {
                return;
            }

            echo sprintf("[call] Recovered price for message #%d: %s %s\n", $message->getId(), $twilioCall->getPrice(), $twilioCall->getUnit());

            $this->saveCallCost($twilioCall, $message);
        });
    }
}