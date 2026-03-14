<?php

namespace App\Tests\Services;

use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Volunteer;
use App\Manager\LanguageConfigManager;
use App\Manager\PhoneConfigManager;
use App\Model\LanguageConfig;
use App\Services\MessageFormatter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class MessageFormatterTest extends TestCase
{
    private $phoneConfigManager;
    private $languageManager;
    private $router;
    private $translator;
    private $templating;
    private $formatter;

    protected function setUp() : void
    {
        $this->phoneConfigManager = $this->createMock(PhoneConfigManager::class);
        $this->languageManager    = $this->createMock(LanguageConfigManager::class);
        $this->router             = $this->createMock(RouterInterface::class);
        $this->translator         = $this->createMock(TranslatorInterface::class);
        $this->templating         = $this->createMock(Environment::class);

        $this->formatter = new MessageFormatter(
            $this->phoneConfigManager,
            $this->languageManager,
            $this->router,
            $this->translator,
            $this->templating
        );
    }

    private function createLanguageConfig() : LanguageConfig
    {
        $config = $this->createMock(LanguageConfig::class);
        $config->method('getBrand')->willReturn('Croix-Rouge');
        $config->method('getLocale')->willReturn('fr');

        return $config;
    }

    private function createMessageWithCommunication(string $type, string $body = 'Test body') : array
    {
        $communication = $this->createMock(Communication::class);
        $communication->method('getType')->willReturn($type);
        $communication->method('getBody')->willReturn($body);
        $communication->method('getChoices')->willReturn(new ArrayCollection());
        $communication->method('getCreatedAt')->willReturn(new \DateTime('2026-01-15 14:30:00'));
        $communication->method('isMultipleAnswer')->willReturn(false);
        $communication->method('getShortcut')->willReturn(null);
        $communication->method('getSubject')->willReturn(null);
        $communication->method('getLanguage')->willReturn('fr');

        $volunteer = $this->createMock(Volunteer::class);
        $volunteer->method('needsShortcutInMessages')->willReturn(false);
        $volunteer->method('isOnlyOutboundSms')->willReturn(false);

        $message = $this->createMock(Message::class);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('getVolunteer')->willReturn($volunteer);
        $message->method('getCode')->willReturn('ABCD1234');
        $message->method('getPrefix')->willReturn('AB');

        return [$message, $communication, $volunteer];
    }

    public function testFormatMessageContentDispatchesSms()
    {
        [$message, $communication] = $this->createMessageWithCommunication(Communication::TYPE_SMS);

        $this->phoneConfigManager->method('getPhoneConfigForVolunteer')->willReturn(null);

        $languageConfig = $this->createLanguageConfig();
        $this->languageManager->method('getLanguageConfigForCommunication')->willReturn($languageConfig);

        $this->translator->method('trans')->willReturn('CROIX-ROUGE announcement');

        $result = $this->formatter->formatMessageContent($message);

        $this->assertIsString($result);
    }

    public function testFormatMessageContentDispatchesCall()
    {
        [$message, $communication] = $this->createMessageWithCommunication(Communication::TYPE_CALL);

        $this->phoneConfigManager->method('getPhoneConfigForVolunteer')->willReturn(null);

        $languageConfig = $this->createLanguageConfig();
        $this->languageManager->method('getLanguageConfigForCommunication')->willReturn($languageConfig);

        $this->translator->method('trans')->willReturn('Call announcement');

        $result = $this->formatter->formatMessageContent($message);

        $this->assertIsString($result);
    }

    public function testFormatMessageContentDispatchesEmail()
    {
        [$message, $communication] = $this->createMessageWithCommunication(Communication::TYPE_EMAIL);

        $this->phoneConfigManager->method('getPhoneConfigForVolunteer')->willReturn(null);

        $languageConfig = $this->createLanguageConfig();
        $this->languageManager->method('getLanguageConfigForCommunication')->willReturn($languageConfig);

        $this->templating->method('render')->willReturn('<html>email content</html>');

        $result = $this->formatter->formatMessageContent($message);

        $this->assertStringContainsString('email content', $result);
    }

    public function testFormatSmsContentWithNoChoices()
    {
        [$message] = $this->createMessageWithCommunication(Communication::TYPE_SMS, 'Emergency alert');

        $this->phoneConfigManager->method('getPhoneConfigForVolunteer')->willReturn(null);

        $languageConfig = $this->createLanguageConfig();
        $this->languageManager->method('getLanguageConfigForCommunication')->willReturn($languageConfig);

        $this->translator->method('trans')->willReturn('CROIX-ROUGE');

        $result = $this->formatter->formatSMSContent($message);

        $this->assertIsString($result);
        $this->assertStringContainsString('Emergency alert', $result);
    }

    public function testFormatSimpleSmsContent()
    {
        $volunteer = $this->createMock(Volunteer::class);

        $this->phoneConfigManager->method('getPhoneConfigForVolunteer')->willReturn(null);

        $languageConfig = $this->createLanguageConfig();
        $this->languageManager->method('getLanguageConfig')->willReturn($languageConfig);

        $this->translator->method('trans')->willReturn('CROIX-ROUGE');

        $result = $this->formatter->formatSimpleSMSContent($volunteer, 'Simple message');

        $this->assertIsString($result);
        $this->assertStringContainsString('Simple message', $result);
    }

    public function testFormatCallContentWithoutChoices()
    {
        [$message] = $this->createMessageWithCommunication(Communication::TYPE_CALL, 'Call body');

        $this->phoneConfigManager->method('getPhoneConfigForVolunteer')->willReturn(null);

        $languageConfig = $this->createLanguageConfig();
        $this->languageManager->method('getLanguageConfigForCommunication')->willReturn($languageConfig);

        $this->translator->method('trans')->willReturn('Announcement');

        $result = $this->formatter->formatCallContent($message, false);

        $this->assertIsString($result);
        $this->assertStringContainsString('Call body.', $result);
    }

    public function testFormatCallContentWithChoices()
    {
        [$message, $communication] = $this->createMessageWithCommunication(Communication::TYPE_CALL, 'Call body');

        $choice1 = $this->createMock(Choice::class);
        $choice1->method('getCode')->willReturn('1');
        $choice1->method('getLabel')->willReturn('Yes');

        $choice2 = $this->createMock(Choice::class);
        $choice2->method('getCode')->willReturn('2');
        $choice2->method('getLabel')->willReturn('No');

        $communication->method('getChoices')->willReturn(new ArrayCollection([$choice1, $choice2]));

        $this->phoneConfigManager->method('getPhoneConfigForVolunteer')->willReturn(null);

        $languageConfig = $this->createLanguageConfig();
        $this->languageManager->method('getLanguageConfigForCommunication')->willReturn($languageConfig);

        $this->translator->method('trans')->willReturn('translated text');

        $result = $this->formatter->formatCallContent($message, true);

        $this->assertIsString($result);
    }

    public function testFormatCallChoicesContent()
    {
        [$message, $communication] = $this->createMessageWithCommunication(Communication::TYPE_CALL);

        $choice = $this->createMock(Choice::class);
        $choice->method('getCode')->willReturn('1');
        $choice->method('getLabel')->willReturn('Available');

        $communication->method('getChoices')->willReturn(new ArrayCollection([$choice]));

        $this->phoneConfigManager->method('getPhoneConfigForVolunteer')->willReturn(null);

        $languageConfig = $this->createLanguageConfig();
        $this->languageManager->method('getLanguageConfigForCommunication')->willReturn($languageConfig);

        $this->translator->method('trans')->willReturn('Press key');

        $result = $this->formatter->formatCallChoicesContent($message);

        $this->assertIsString($result);
    }

    public function testFormatTextEmailContentWithSubject()
    {
        $communication = $this->createMock(Communication::class);
        $communication->method('getType')->willReturn(Communication::TYPE_EMAIL);
        $communication->method('getBody')->willReturn('<b>Email body</b>');
        $communication->method('getChoices')->willReturn(new ArrayCollection());
        $communication->method('getCreatedAt')->willReturn(new \DateTime());
        $communication->method('isMultipleAnswer')->willReturn(false);
        $communication->method('getSubject')->willReturn('Important Subject');
        $communication->method('getLanguage')->willReturn('fr');

        $volunteer = $this->createMock(Volunteer::class);

        $message = $this->createMock(Message::class);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('getVolunteer')->willReturn($volunteer);
        $message->method('getCode')->willReturn('ABCD1234');

        $this->phoneConfigManager->method('getPhoneConfigForVolunteer')->willReturn(null);

        $languageConfig = $this->createLanguageConfig();
        $this->languageManager->method('getLanguageConfigForCommunication')->willReturn($languageConfig);

        $this->translator->method('trans')->willReturn('Email announcement');

        $result = $this->formatter->formatTextEmailContent($message);

        $this->assertIsString($result);
        $this->assertStringContainsString('Important Subject', $result);
        // HTML tags should be stripped
        $this->assertStringContainsString('Email body', $result);
        $this->assertStringNotContainsString('<b>', $result);
    }

    public function testFormatTextEmailContentWithoutSubject()
    {
        [$message, $communication] = $this->createMessageWithCommunication(Communication::TYPE_EMAIL, 'Plain email body');

        $this->phoneConfigManager->method('getPhoneConfigForVolunteer')->willReturn(null);

        $languageConfig = $this->createLanguageConfig();
        $this->languageManager->method('getLanguageConfigForCommunication')->willReturn($languageConfig);

        $this->translator->method('trans')->willReturn('Email announcement');

        $result = $this->formatter->formatTextEmailContent($message);

        $this->assertIsString($result);
        $this->assertStringContainsString('Plain email body', $result);
    }

    public function testFormatTextEmailContentWithChoices()
    {
        $choice = $this->createMock(Choice::class);
        $choice->method('getCode')->willReturn('1');
        $choice->method('getLabel')->willReturn('Accept');

        $communication = $this->createMock(Communication::class);
        $communication->method('getType')->willReturn(Communication::TYPE_EMAIL);
        $communication->method('getBody')->willReturn('body');
        $communication->method('getChoices')->willReturn(new ArrayCollection([$choice]));
        $communication->method('getCreatedAt')->willReturn(new \DateTime());
        $communication->method('isMultipleAnswer')->willReturn(false);
        $communication->method('getSubject')->willReturn(null);
        $communication->method('getLanguage')->willReturn('fr');

        $volunteer = $this->createMock(Volunteer::class);

        $message = $this->createMock(Message::class);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('getVolunteer')->willReturn($volunteer);
        $message->method('getCode')->willReturn('ABCD1234');

        $this->phoneConfigManager->method('getPhoneConfigForVolunteer')->willReturn(null);

        $languageConfig = $this->createLanguageConfig();
        $this->languageManager->method('getLanguageConfigForCommunication')->willReturn($languageConfig);

        $this->router->method('generate')->willReturn('/msg/ABCD1234');

        $this->translator->method('trans')->willReturn('translated');

        $result = $this->formatter->formatTextEmailContent($message);

        $this->assertIsString($result);
        $this->assertStringContainsString('1: Accept', $result);
    }

    public function testFormatHtmlEmailContent()
    {
        [$message] = $this->createMessageWithCommunication(Communication::TYPE_EMAIL);

        $this->phoneConfigManager->method('getPhoneConfigForVolunteer')->willReturn(null);

        $languageConfig = $this->createLanguageConfig();
        $this->languageManager->method('getLanguageConfigForCommunication')->willReturn($languageConfig);

        $this->templating->method('render')->willReturn('<html><body>Rendered email</body></html>');

        $result = $this->formatter->formatHtmlEmailContent($message);

        $this->assertSame('<html><body>Rendered email</body></html>', $result);
    }

    public function testFormatCallContentHoursAndMinutesHandleZero()
    {
        // Test the ltrim behavior for hours/minutes with leading zeros
        $communication = $this->createMock(Communication::class);
        $communication->method('getType')->willReturn(Communication::TYPE_CALL);
        $communication->method('getBody')->willReturn('Body');
        $communication->method('getChoices')->willReturn(new ArrayCollection());
        $communication->method('getCreatedAt')->willReturn(new \DateTime('2026-01-15 00:00:00'));
        $communication->method('getLanguage')->willReturn('fr');

        $volunteer = $this->createMock(Volunteer::class);
        $message = $this->createMock(Message::class);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('getVolunteer')->willReturn($volunteer);

        $this->phoneConfigManager->method('getPhoneConfigForVolunteer')->willReturn(null);

        $languageConfig = $this->createLanguageConfig();
        $this->languageManager->method('getLanguageConfigForCommunication')->willReturn($languageConfig);

        // Capture the translation parameters to check hours/mins handling
        $capturedParams = [];
        $this->translator->method('trans')->willReturnCallback(function ($key, $params) use (&$capturedParams) {
            if (strpos($key, 'announcement') !== false) {
                $capturedParams = $params;
            }
            return 'translated';
        });

        $this->formatter->formatCallContent($message, false);

        // When hour is "00", ltrim('00', 0) returns '', then $hours is set to 0
        $this->assertSame(0, $capturedParams['%hours%'] ?? null);
        $this->assertSame(0, $capturedParams['%mins%'] ?? null);
    }
}
