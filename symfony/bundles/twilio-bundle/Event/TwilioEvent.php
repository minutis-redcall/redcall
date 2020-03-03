<?php

namespace Bundles\TwilioBundle\Event;

use Bundles\TwilioBundle\Entity\TwilioMessage;

class TwilioEvent
{
    /**
     * @var TwilioMessage
     */
    private $message;

    /**
     * @param TwilioMessage $message
     */
    public function __construct(TwilioMessage $message)
    {
        $this->message = $message;
    }

    /**
     * @return TwilioMessage
     */
    public function getMessage(): TwilioMessage
    {
        return $this->message;
    }
}