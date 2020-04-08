<?php

namespace Bundles\TwilioBundle\Event;

use Bundles\TwilioBundle\Entity\TwilioMessage;

class TwilioMessageEvent
{
    /**
     * @var TwilioMessage
     */
    private $message;

    public function __construct(TwilioMessage $message)
    {
        $this->message = $message;
    }

    public function getMessage(): TwilioMessage
    {
        return $this->message;
    }
}