<?php

namespace Bundles\TwilioBundle\Event;

use App\Entity\Message;
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
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }
}