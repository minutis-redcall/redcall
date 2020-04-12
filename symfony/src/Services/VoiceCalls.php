<?php

namespace App\Services;

use App\Entity\Message;
use App\Manager\MediaManager;
use App\Manager\MessageManager;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Twilio\TwiML\VoiceResponse;

class VoiceCalls
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var MessageFormatter
     */
    private $formatter;

    /**
     * @var MediaManager
     */
    private $mediaManager;

    /**
     * @var MessageManager
     */
    private $messageManager;

    public function __construct(RouterInterface $router, TranslatorInterface $translator, MessageFormatter $formatter, MediaManager $mediaManager, MessageManager $messageManager)
    {
        $this->router = $router;
        $this->translator = $translator;
        $this->formatter = $formatter;
        $this->mediaManager = $mediaManager;
        $this->messageManager = $messageManager;
    }

    public function establishCall(Message $message)
    {
        return $this->getVoiceResponse(
            $this->formatter->formatCallContent($message)
        );
    }

    public function handleKeyPress(Message $message, int $digit)
    {
        // Repeat
        if (0 === $digit) {
            return $this->establishCall($message);
        }

        $answer = sprintf('%s%s', $message->getPrefix(), $digit);
        $choice = $message->getCommunication()->getChoiceByCode($message->getPrefix(), $answer);

        // Invalid answer
        if (!$choice) {
            $text = sprintf(
                '%s %s',
                $this->translator->trans('message.call.unknown'),
                $this->formatter->formatCallChoicesContent($message)
            );

            return $this->getVoiceResponse($text);
        }

        $this->messageManager->addAnswer($message, $answer);

        // Answer saved, thanks
        $text = $this->translator->trans('message.call.answer', [
            '%choice%' => $choice->getLabel(),
        ]);

        return $this->getVoiceResponse($text);
    }

    private function getMediaUrl(string $text): string
    {
        $uuid = $this->mediaManager->createMp3($text);

        $relativeUrl = $this->router->generate('media_play', [
            'uuid' => $uuid,
        ]);

        $absoluteUrl = sprintf('%s%s', trim(getenv('WEBSITE_URL'), '/'), $relativeUrl);

        return $absoluteUrl;
    }

    private function getVoiceResponse(string $text): VoiceResponse
    {
        $url = $this->getMediaUrl($text);

        $response = new VoiceResponse();
        $gather = $response->gather(['numDigits' => 1]);
        $gather->play($url);

        return $response;
    }
}