<?php

namespace App\Tests\Services;

use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Volunteer;
use App\Manager\LanguageConfigManager;
use App\Manager\MediaManager;
use App\Manager\MessageManager;
use App\Model\LanguageConfig;
use App\Model\TextToSpeechConfig;
use App\Services\MessageFormatter;
use App\Services\VoiceCalls;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twilio\TwiML\VoiceResponse;

class VoiceCallsTest extends TestCase
{
    private $router;
    private $translator;
    private $formatter;
    private $mediaManager;
    private $messageManager;
    private $languageManager;
    private $voiceCalls;

    protected function setUp() : void
    {
        $this->router          = $this->createMock(RouterInterface::class);
        $this->translator      = $this->createMock(TranslatorInterface::class);
        $this->formatter       = $this->createMock(MessageFormatter::class);
        $this->mediaManager    = $this->createMock(MediaManager::class);
        $this->messageManager  = $this->createMock(MessageManager::class);
        $this->languageManager = $this->createMock(LanguageConfigManager::class);

        $this->voiceCalls = new VoiceCalls(
            $this->router,
            $this->translator,
            $this->formatter,
            $this->mediaManager,
            $this->messageManager,
            $this->languageManager
        );
    }

    private function createTestMessage() : Message
    {
        $communication = $this->createMock(Communication::class);
        $communication->method('getChoices')->willReturn(new ArrayCollection());
        $communication->method('getLanguage')->willReturn('fr');

        $volunteer = $this->createMock(Volunteer::class);

        $message = $this->createMock(Message::class);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('getVolunteer')->willReturn($volunteer);
        $message->method('getPrefix')->willReturn('AB');

        return $message;
    }

    private function setupLanguageAndMedia() : void
    {
        $ttsConfig = $this->createMock(TextToSpeechConfig::class);

        $languageConfig = $this->createMock(LanguageConfig::class);
        $languageConfig->method('getTextToSpeech')->willReturn($ttsConfig);

        $this->languageManager->method('getLanguageConfigForCommunication')->willReturn($languageConfig);

        $media = new \stdClass();
        $media->url = 'https://example.com/media.mp3';

        $mediaMock = $this->createMock(\App\Entity\Media::class);
        $mediaMock->method('getUrl')->willReturn('https://example.com/media.mp3');

        $this->mediaManager->method('createMp3')->willReturn($mediaMock);
    }

    public function testEstablishCallReturnsVoiceResponse()
    {
        $message = $this->createTestMessage();
        $this->setupLanguageAndMedia();

        $this->formatter->method('formatCallContent')->willReturn('Call content');
        $this->formatter->method('formatCallChoicesContent')->willReturn('Press 1 for yes');

        $this->router->method('generate')->willReturn('/twilio/outgoing-call/test-uuid');

        $result = $this->voiceCalls->establishCall('test-uuid', $message);

        $this->assertInstanceOf(VoiceResponse::class, $result);
    }

    public function testHandleKeyPressWithNonNumericDigitReturnsInvalidResponse()
    {
        $message = $this->createTestMessage();
        $this->setupLanguageAndMedia();

        $this->formatter->method('formatCallChoicesContent')->willReturn('Press a key');
        $this->translator->method('trans')->willReturn('Invalid answer');
        $this->router->method('generate')->willReturn('/twilio/outgoing-call/uuid');

        $result = $this->voiceCalls->handleKeyPress('uuid', $message, '#');

        $this->assertInstanceOf(VoiceResponse::class, $result);
    }

    public function testHandleKeyPressWithStarReturnsInvalidResponse()
    {
        $message = $this->createTestMessage();
        $this->setupLanguageAndMedia();

        $this->formatter->method('formatCallChoicesContent')->willReturn('Press a key');
        $this->translator->method('trans')->willReturn('Invalid answer');
        $this->router->method('generate')->willReturn('/twilio/outgoing-call/uuid');

        $result = $this->voiceCalls->handleKeyPress('uuid', $message, '*');

        $this->assertInstanceOf(VoiceResponse::class, $result);
    }

    public function testHandleKeyPressWithZeroRepeatsCall()
    {
        $message = $this->createTestMessage();
        $this->setupLanguageAndMedia();

        $this->formatter->method('formatCallContent')->willReturn('Call content');
        $this->formatter->method('formatCallChoicesContent')->willReturn('Choices');
        $this->router->method('generate')->willReturn('/twilio/outgoing-call/uuid');

        $result = $this->voiceCalls->handleKeyPress('uuid', $message, '0');

        $this->assertInstanceOf(VoiceResponse::class, $result);
    }

    public function testHandleKeyPressWithInvalidChoiceReturnsInvalidResponse()
    {
        $communication = $this->createMock(Communication::class);
        $communication->method('getChoices')->willReturn(new ArrayCollection());
        $communication->method('getChoiceByCode')->willReturn(null);
        $communication->method('getLanguage')->willReturn('fr');

        $message = $this->createMock(Message::class);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('getVolunteer')->willReturn($this->createMock(Volunteer::class));
        $message->method('getPrefix')->willReturn('AB');

        $this->setupLanguageAndMedia();
        $this->formatter->method('formatCallChoicesContent')->willReturn('Choices');
        $this->translator->method('trans')->willReturn('Invalid');
        $this->router->method('generate')->willReturn('/twilio/outgoing-call/uuid');

        $result = $this->voiceCalls->handleKeyPress('uuid', $message, '9');

        $this->assertInstanceOf(VoiceResponse::class, $result);
    }

    public function testHandleKeyPressWithValidChoiceSavesAnswer()
    {
        $choice = $this->createMock(Choice::class);
        $choice->method('getLabel')->willReturn('Yes');

        $communication = $this->createMock(Communication::class);
        $communication->method('getChoices')->willReturn(new ArrayCollection([$choice]));
        $communication->method('getChoiceByCode')->willReturn($choice);
        $communication->method('getLanguage')->willReturn('fr');

        $message = $this->createMock(Message::class);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('getVolunteer')->willReturn($this->createMock(Volunteer::class));
        $message->method('getPrefix')->willReturn('AB');

        $this->setupLanguageAndMedia();
        $this->translator->method('trans')->willReturn('Thank you');

        $this->messageManager->expects($this->once())
            ->method('addAnswer')
            ->with($message, 'AB1');

        $result = $this->voiceCalls->handleKeyPress('uuid', $message, '1');

        $this->assertInstanceOf(VoiceResponse::class, $result);
    }
}
