<?php

namespace Bundles\TwilioBundle\Event;

use Bundles\TwilioBundle\Entity\TwilioMessage;
use Twilio\TwiML\MessagingResponse;

class TwilioMessageEvent
{
    /**
     * @var TwilioMessage
     */
    private $message;

    /**
     * @var MessagingResponse|null
     */
    private $response;

    public function __construct(TwilioMessage $message)
    {
        $this->message = $message;
    }

    public function getMessage(): TwilioMessage
    {
        return $this->message;
    }

    public function getResponse(): ?MessagingResponse
    {
        return $this->response;
    }

    public function setResponse(?MessagingResponse $response): TwilioMessageEvent
    {
        $this->response = $response;

        return $this;
    }
}