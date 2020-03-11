<?php

namespace App\Manager;

use App\Entity\Cost;
use App\Entity\Message;
use App\Repository\CostRepository;

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
     * @param CostRepository $costRepository
     * @param MessageManager $messageManager
     */
    public function __construct(CostRepository $costRepository, MessageManager $messageManager)
    {
        $this->costRepository = $costRepository;
        $this->messageManager = $messageManager;
    }

    /**
     * @param string       $direction
     * @param string       $fromNumber
     * @param string       $toNumber
     * @param string       $body
     * @param string       $price
     * @param string       $currency
     * @param Message|null $message
     */
    public function saveCost(string $direction, string $fromNumber, string $toNumber, string $body, string $price, string $currency, Message $message = null)
    {
        $cost = new Cost();
        $cost->setDirection($direction);
        $cost->setFromNumber($fromNumber);
        $cost->setToNumber($toNumber);
        $cost->setBody($body);
        $cost->setPrice($price);
        $cost->setCurrency($currency);

        if ($message) {
            $message->addCost($cost);
        }

        $this->costRepository->save($cost);

        if ($message) {
            $this->messageManager->save($message);
        }
    }
}