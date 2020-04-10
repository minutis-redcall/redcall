<?php

namespace App\Services;

use App\Entity\Message;
use App\Manager\MediaManager;
use Symfony\Component\Routing\RouterInterface;
use Twilio\TwiML\VoiceResponse;

class VoiceCalls
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var MessageFormatter
     */
    private $formatter;

    /**
     * @var MediaManager
     */
    private $mediaManager;

    /**
     * @param RouterInterface  $router
     * @param MessageFormatter $formatter
     * @param MediaManager     $mediaManager
     */
    public function __construct(RouterInterface $router, MessageFormatter $formatter, MediaManager $mediaManager)
    {
        $this->router = $router;
        $this->formatter = $formatter;
        $this->mediaManager = $mediaManager;
    }

    public function establishCall(Message $message)
    {
        $uuid = $this->mediaManager->findUuidByText(
            $this->formatter->formatCallContent($message)
        );

        $url = trim(getenv('WEBSITE_URL'), '/').$this->router->generate('twilio_outgoing_call', [
                'uuid' => $entity->getUuid(),
            ])

        $response = new VoiceResponse();
        $gather = $response->gather(['numDigits' => 1]);
        $gather->play()

        $this->say($gather, implode(' ', $this->formatter->formatCallContent($message->getCommunication())));

        $event->setResponse($response);


    }

    public function handleKeyPress(Message $message, int $digit)
    {

    }
}