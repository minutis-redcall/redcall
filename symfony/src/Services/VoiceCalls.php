<?php

namespace App\Services;

use App\Entity\Message;
use App\Manager\MediaManager;
use App\Manager\MessageManager;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
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

    /**
     * @param RouterInterface     $router
     * @param TranslatorInterface $translator
     * @param MessageFormatter    $formatter
     * @param MediaManager        $mediaManager
     * @param MessageManager      $messageManager
     */
    public function __construct(RouterInterface $router,
        TranslatorInterface $translator,
        MessageFormatter $formatter,
        MediaManager $mediaManager,
        MessageManager $messageManager)
    {
        $this->router         = $router;
        $this->translator     = $translator;
        $this->formatter      = $formatter;
        $this->mediaManager   = $mediaManager;
        $this->messageManager = $messageManager;
    }

    public function establishCall(string $uuid, Message $message) : VoiceResponse
    {
        return $this->getVoiceResponse(
            $uuid,
            $this->formatter->formatCallContent($message, false),
            $this->formatter->formatCallChoicesContent($message)
        );
    }

    public function handleKeyPress(string $uuid, Message $message, string $digit) : VoiceResponse
    {
        // #, *
        if (!is_numeric($digit)) {
            return $this->getInvalidAnswerResponse($uuid, $message);
        }

        $digit = intval($digit);

        // Repeat
        if (0 === $digit) {
            return $this->establishCall($uuid, $message);
        }

        $answer = sprintf('%s%s', $message->getPrefix(), $digit);
        $choice = $message->getCommunication()->getChoiceByCode($message->getPrefix(), $answer);

        // Invalid answer
        if (!$choice) {
            return $this->getInvalidAnswerResponse($uuid, $message);
        }

        $this->messageManager->addAnswer($message, $answer);

        // Answer saved, thanks
        $text = $this->translator->trans('message.call.answer', [
            '%choice%' => $choice->getLabel(),
        ]);

        return $this->getVoiceResponse($uuid, $text);
    }

    private function getInvalidAnswerResponse(string $uuid, Message $message) : VoiceResponse
    {
        return $this->getVoiceResponse(
            $uuid,
            $this->translator->trans('message.call.unknown'),
            $this->formatter->formatCallChoicesContent($message)
        );
    }

    private function getMediaUrl(string $text, bool $male = false) : string
    {
        $media = $this->mediaManager->createMp3($text, $male);

        return $media->getUrl();
    }

    private function getVoiceResponse(string $uuid, string $text, string $gather = null) : VoiceResponse
    {
        $response = new VoiceResponse();

        $response->play(
            $this->getMediaUrl($text)
        );

        if ($gather) {
            $url = $this->router->generate('twilio_outgoing_call', [
                'uuid' => $uuid,
            ]);

            $keypad = $response->gather([
                'numDigits' => 1,
                'action'    => trim(getenv('WEBSITE_URL'), '/').$url,
            ]);

            $keypad->play(
                $this->getMediaUrl($gather, true)
            );
        }

        return $response;
    }
}