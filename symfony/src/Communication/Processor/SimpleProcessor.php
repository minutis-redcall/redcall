<?php

namespace App\Communication\Processor;

use App\Communication\Sender;
use App\Entity\Communication;

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
        $this->sender->sendCommunication($communication);
    }
}