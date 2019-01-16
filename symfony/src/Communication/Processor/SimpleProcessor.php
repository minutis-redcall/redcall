<?php

namespace App\Communication\Processor;

use App\Communication\Sender;
use App\Entity\Communication;
use App\Entity\Message;

class SimpleProcessor implements ProcessorInterface
{
    /** @var Sender */
    private $sender;

    /**
     * SimpleProcessor constructor.
     *
     * @param Sender $sender
     */
    public function __construct(Sender $sender)
    {
        $this->sender = $sender;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Communication $communication)
    {
        /** @var Message $message */
        foreach ($communication->getMessages() as $message) {
            $this->sender->send($message);
        }
    }
}