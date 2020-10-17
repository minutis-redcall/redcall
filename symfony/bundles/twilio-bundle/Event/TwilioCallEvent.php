<?php

namespace Bundles\TwilioBundle\Event;

use Bundles\TwilioBundle\Entity\TwilioCall;
use Symfony\Component\HttpFoundation\Response;
use Twilio\TwiML\VoiceResponse;

class TwilioCallEvent
{
    /**
     * @var TwilioCall
     */
    private $call;

    /**
     * @var string|null
     */
    private $keyPressed;

    /**
     * @var VoiceResponse|Response|null
     */
    private $response;

    public function __construct(TwilioCall $call, string $keyPressed = null)
    {
        $this->call       = $call;
        $this->keyPressed = $keyPressed;
    }

    public function getCall(): TwilioCall
    {
        return $this->call;
    }

    public function getKeyPressed(): ?string
    {
        return $this->keyPressed;
    }

    /**
     * @return VoiceResponse|Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param VoiceResponse|Response $response
     *
     * @return TwilioCallEvent
     */
    public function setResponse($response): TwilioCallEvent
    {
        $this->response = $response;

        return $this;
    }
}