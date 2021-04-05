<?php

namespace App\Services;

use App\Entity\Communication;
use App\Entity\Message;
use App\Manager\LanguageConfigManager;
use App\Manager\MediaManager;
use App\Manager\MessageManager;
use App\Model\TextToSpeechConfig;
use Doctrine\Common\Collections\Collection;
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
     * @var LanguageConfigManager
     */
    private $languageManager;

    public function __construct(RouterInterface $router,
        TranslatorInterface $translator,
        MessageFormatter $formatter,
        MediaManager $mediaManager,
        MessageManager $messageManager,
        LanguageConfigManager $languageManager)
    {
        $this->router          = $router;
        $this->translator      = $translator;
        $this->formatter       = $formatter;
        $this->mediaManager    = $mediaManager;
        $this->messageManager  = $messageManager;
        $this->languageManager = $languageManager;
    }

    public function establishCall(string $uuid, Message $message) : VoiceResponse
    {
        return $this->getVoiceResponse(
            $uuid,
            $this->getTextToSpeechConfig($message->getCommunication()),
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
        ], null, $message->getCommunication()->getLanguage());

        $config = $this->getTextToSpeechConfig($message->getCommunication());

        return $this->getVoiceResponse($uuid, $config, $text);
    }

    public function prepareMedias(Communication $communication)
    {
        $messages = $communication->getMessages();
        if ($messages instanceof Collection) {
            $messages = $messages->toArray();
        }
        $message = reset($messages);

        $config = $this->getTextToSpeechConfig($communication);

        $this->getMediaUrl($config, $this->formatter->formatCallContent($message, false));
        if (0 !== $communication->getChoices()->count()) {
            $this->getMediaUrl($config, $this->formatter->formatCallChoicesContent($message, false), true);
        }
    }

    private function getTextToSpeechConfig(Communication $communication) : TextToSpeechConfig
    {
        return $this->languageManager->getLanguageConfigForCommunication($communication)->getTextToSpeech();
    }

    private function getInvalidAnswerResponse(string $uuid, Message $message) : VoiceResponse
    {
        return $this->getVoiceResponse(
            $uuid,
            $this->getTextToSpeechConfig($message->getCommunication()),
            $this->translator->trans('message.call.unknown'),
            $this->formatter->formatCallChoicesContent($message)
        );
    }

    private function getMediaUrl(TextToSpeechConfig $config, string $text, bool $male = false) : string
    {
        $media = $this->mediaManager->createMp3($config, $text, $male);

        return $media->getUrl();
    }

    private function getVoiceResponse(string $uuid,
        TextToSpeechConfig $config,
        string $text,
        string $gather = null) : VoiceResponse
    {
        $response = new VoiceResponse();

        $response->play(
            $this->getMediaUrl($config, $text)
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
                $this->getMediaUrl($config, $gather, true)
            );
        }

        return $response;
    }
}