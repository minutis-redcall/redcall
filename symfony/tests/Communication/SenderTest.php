<?php

namespace App\Tests\Communication;

use App\Communication\Sender;
use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Phone;
use App\Entity\Volunteer;
use App\Manager\MessageManager;
use App\Manager\PhoneConfigManager;
use App\Model\PhoneConfig;
use App\Provider\Call\CallProvider;
use App\Provider\Email\EmailProvider;
use App\Provider\SMS\SMSProvider;
use App\Services\MessageFormatter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SenderTest extends TestCase
{
    private $phoneConfigManager;
    private $smsProvider;
    private $callProvider;
    private $emailProvider;
    private $formatter;
    private $messageManager;
    private $logger;
    private $sender;

    protected function setUp() : void
    {
        $this->phoneConfigManager = $this->createMock(PhoneConfigManager::class);
        $this->smsProvider        = $this->createMock(SMSProvider::class);
        $this->callProvider       = $this->createMock(CallProvider::class);
        $this->emailProvider      = $this->createMock(EmailProvider::class);
        $this->formatter          = $this->createMock(MessageFormatter::class);
        $this->messageManager     = $this->createMock(MessageManager::class);
        $this->logger             = $this->createMock(LoggerInterface::class);

        $this->sender = new Sender(
            $this->phoneConfigManager,
            $this->smsProvider,
            $this->callProvider,
            $this->emailProvider,
            $this->formatter,
            $this->messageManager,
            $this->logger
        );
    }

    private function createVolunteer(
        ?string $phoneNumber = '+33612345678',
        bool $phoneOptin = true,
        ?string $email = 'test@example.com',
        bool $emailOptin = true,
        bool $mobile = true,
        ?\DateTimeInterface $optoutUntil = null
    ) : Volunteer {
        $volunteer = $this->createMock(Volunteer::class);
        $volunteer->method('getPhoneNumber')->willReturn($phoneNumber);
        $volunteer->method('isPhoneNumberOptin')->willReturn($phoneOptin);
        $volunteer->method('getEmail')->willReturn($email);
        $volunteer->method('isEmailOptin')->willReturn($emailOptin);
        $volunteer->method('getOptoutUntil')->willReturn($optoutUntil);

        if ($phoneNumber) {
            $phone = $this->createMock(Phone::class);
            $phone->method('isMobile')->willReturn($mobile);
            $volunteer->method('getPhone')->willReturn($phone);
        } else {
            $volunteer->method('getPhone')->willReturn(null);
        }

        return $volunteer;
    }

    private function createMessage(Volunteer $volunteer, Communication $communication, bool $sent = false) : Message
    {
        $message = $this->createMock(Message::class);
        $message->method('getVolunteer')->willReturn($volunteer);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('isSent')->willReturn($sent);
        $message->method('canBeSent')->willReturn(!$sent);

        return $message;
    }

    private function createCommunication(string $type) : Communication
    {
        $communication = $this->createMock(Communication::class);
        $communication->method('getType')->willReturn($type);
        $communication->method('getSubject')->willReturn('Test Subject');

        return $communication;
    }

    // --- isMessageNotTransmittable tests ---

    public function testSmsNotTransmittableWhenVolunteerOptedOut()
    {
        $futureDate = new \DateTime('+1 day');
        $volunteer = $this->createVolunteer('+33612345678', true, null, false, true, $futureDate);
        $communication = $this->createCommunication(Communication::TYPE_SMS);

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $message->method('getVolunteer')->willReturn($volunteer);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('isSent')->willReturn(false);
        $message->method('canBeSent')->willReturn(true);

        $message->expects($this->once())
            ->method('setError')
            ->with('campaign_status.warning.optout_until');

        $this->messageManager->expects($this->once())->method('save');

        $this->sender->sendMessage($message, false);
    }

    public function testSmsNotTransmittableWhenPhoneNotMobile()
    {
        $volunteer = $this->createVolunteer('+33112345678', true, null, false, false);
        $communication = $this->createCommunication(Communication::TYPE_SMS);

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $message->method('getVolunteer')->willReturn($volunteer);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('isSent')->willReturn(false);
        $message->method('canBeSent')->willReturn(true);

        $message->expects($this->once())
            ->method('setError')
            ->with('campaign_status.warning.no_phone_mobile');

        $this->messageManager->expects($this->once())->method('save');

        $this->sender->sendMessage($message, false);
    }

    public function testSmsNotTransmittableWhenNoPhone()
    {
        $volunteer = $this->createVolunteer(null);
        $communication = $this->createCommunication(Communication::TYPE_SMS);

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $message->method('getVolunteer')->willReturn($volunteer);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('isSent')->willReturn(false);
        $message->method('canBeSent')->willReturn(true);

        $message->expects($this->once())
            ->method('setError')
            ->with('campaign_status.warning.no_phone');

        $this->messageManager->expects($this->once())->method('save');

        $this->sender->sendMessage($message, false);
    }

    public function testSmsNotTransmittableWhenPhoneNotOptin()
    {
        $volunteer = $this->createVolunteer('+33612345678', false);
        $communication = $this->createCommunication(Communication::TYPE_SMS);

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $message->method('getVolunteer')->willReturn($volunteer);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('isSent')->willReturn(false);
        $message->method('canBeSent')->willReturn(true);

        $message->expects($this->once())
            ->method('setError')
            ->with('campaign_status.warning.no_phone_optin');

        $this->messageManager->expects($this->once())->method('save');

        $this->sender->sendMessage($message, false);
    }

    public function testSmsNotTransmittableWhenCountryDoesNotSupportSms()
    {
        $volunteer = $this->createVolunteer('+33612345678', true);
        $communication = $this->createCommunication(Communication::TYPE_SMS);

        $this->phoneConfigManager->method('isSMSTransmittable')->willReturn(false);

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $message->method('getVolunteer')->willReturn($volunteer);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('isSent')->willReturn(false);
        $message->method('canBeSent')->willReturn(true);

        $message->expects($this->once())
            ->method('setError')
            ->with('campaign_status.warning.country_no_sms');

        $this->sender->sendMessage($message, false);
    }

    public function testCallNotTransmittableWhenNoPhone()
    {
        $volunteer = $this->createVolunteer(null);
        $communication = $this->createCommunication(Communication::TYPE_CALL);

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $message->method('getVolunteer')->willReturn($volunteer);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('isSent')->willReturn(false);
        $message->method('canBeSent')->willReturn(true);

        $message->expects($this->once())
            ->method('setError')
            ->with('campaign_status.warning.no_phone');

        $this->sender->sendMessage($message, false);
    }

    public function testCallNotTransmittableWhenNotOptin()
    {
        $volunteer = $this->createVolunteer('+33612345678', false);
        $communication = $this->createCommunication(Communication::TYPE_CALL);

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $message->method('getVolunteer')->willReturn($volunteer);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('isSent')->willReturn(false);
        $message->method('canBeSent')->willReturn(true);

        $message->expects($this->once())
            ->method('setError')
            ->with('campaign_status.warning.no_phone_optin');

        $this->sender->sendMessage($message, false);
    }

    public function testCallNotTransmittableWhenCountryDoesNotSupportCall()
    {
        $volunteer = $this->createVolunteer('+33612345678', true);
        $communication = $this->createCommunication(Communication::TYPE_CALL);

        $this->phoneConfigManager->method('isVoiceCallTransmittable')->willReturn(false);

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $message->method('getVolunteer')->willReturn($volunteer);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('isSent')->willReturn(false);
        $message->method('canBeSent')->willReturn(true);

        $message->expects($this->once())
            ->method('setError')
            ->with('campaign_status.warning.country_no_call');

        $this->sender->sendMessage($message, false);
    }

    public function testEmailNotTransmittableWhenNoEmail()
    {
        $volunteer = $this->createVolunteer('+33612345678', true, null);
        $communication = $this->createCommunication(Communication::TYPE_EMAIL);

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $message->method('getVolunteer')->willReturn($volunteer);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('isSent')->willReturn(false);
        $message->method('canBeSent')->willReturn(true);

        $message->expects($this->once())
            ->method('setError')
            ->with('campaign_status.warning.no_email');

        $this->sender->sendMessage($message, false);
    }

    public function testEmailNotTransmittableWhenNotOptin()
    {
        $volunteer = $this->createVolunteer('+33612345678', true, 'test@example.com', false);
        $communication = $this->createCommunication(Communication::TYPE_EMAIL);

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $message->method('getVolunteer')->willReturn($volunteer);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('isSent')->willReturn(false);
        $message->method('canBeSent')->willReturn(true);

        $message->expects($this->once())
            ->method('setError')
            ->with('campaign_status.warning.no_email_optin');

        $this->sender->sendMessage($message, false);
    }

    public function testEmailNotTransmittableWhenOptedOut()
    {
        $futureDate = new \DateTime('+1 day');
        $volunteer = $this->createVolunteer('+33612345678', true, 'test@example.com', true, true, $futureDate);
        $communication = $this->createCommunication(Communication::TYPE_EMAIL);

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $message->method('getVolunteer')->willReturn($volunteer);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('isSent')->willReturn(false);
        $message->method('canBeSent')->willReturn(true);

        $message->expects($this->once())
            ->method('setError')
            ->with('campaign_status.warning.optout_until');

        $this->sender->sendMessage($message, false);
    }

    // --- sendCommunication tests ---

    public function testSendCommunicationIteratesOverMessages()
    {
        $volunteer = $this->createVolunteer(null);
        $communication = $this->createCommunication(Communication::TYPE_SMS);

        $message1 = $this->getMockBuilder(Message::class)->disableOriginalConstructor()->getMock();
        $message1->method('getVolunteer')->willReturn($volunteer);
        $message1->method('getCommunication')->willReturn($communication);
        $message1->method('isSent')->willReturn(false);
        $message1->method('canBeSent')->willReturn(true);
        $message1->expects($this->once())->method('setError');

        $message2 = $this->getMockBuilder(Message::class)->disableOriginalConstructor()->getMock();
        $message2->method('getVolunteer')->willReturn($volunteer);
        $message2->method('getCommunication')->willReturn($communication);
        $message2->method('isSent')->willReturn(false);
        $message2->method('canBeSent')->willReturn(true);
        $message2->expects($this->once())->method('setError');

        $communication->method('getMessages')->willReturn(new ArrayCollection([$message1, $message2]));

        $this->sender->sendCommunication($communication);
    }

    public function testSendCommunicationWithForceResetsSentStatus()
    {
        $volunteer = $this->createVolunteer(null);
        $communication = $this->createCommunication(Communication::TYPE_SMS);

        $message = $this->getMockBuilder(Message::class)->disableOriginalConstructor()->getMock();
        $message->method('getVolunteer')->willReturn($volunteer);
        $message->method('getCommunication')->willReturn($communication);
        $message->method('isSent')->willReturn(false);
        $message->method('canBeSent')->willReturn(true);
        $message->expects($this->once())->method('setSent')->with(false);
        $message->expects($this->atLeastOnce())->method('setError');

        $communication->method('getMessages')->willReturn(new ArrayCollection([$message]));

        $this->sender->sendCommunication($communication, true);
    }

    // --- sendEmail tests ---

    public function testSendEmailSuccess()
    {
        $volunteer = $this->createVolunteer('+33612345678', true, 'user@example.com', true);
        $communication = $this->createCommunication(Communication::TYPE_EMAIL);

        $message = new Message();
        $message->setCommunication($communication);
        $message->setVolunteer($volunteer);

        // Use reflection to test sendEmail directly
        $this->phoneConfigManager->method('isSMSTransmittable')->willReturn(true);

        $this->formatter->method('formatTextEmailContent')->willReturn('text content');
        $this->formatter->method('formatHtmlEmailContent')->willReturn('<html>content</html>');

        $this->emailProvider->expects($this->once())
            ->method('send')
            ->with('user@example.com', 'Test Subject', 'text content', '<html>content</html>');

        $this->messageManager->expects($this->once())->method('save');

        // Call sendEmail via reflection
        $reflection = new \ReflectionMethod(Sender::class, 'sendEmail');
        $reflection->setAccessible(true);
        $reflection->invoke($this->sender, $message);
    }

    public function testSendEmailDoesNotSendWhenAlreadySent()
    {
        $volunteer = $this->createVolunteer('+33612345678', true, 'user@example.com', true);
        $communication = $this->createCommunication(Communication::TYPE_EMAIL);

        $message = new Message();
        $message->setCommunication($communication);
        $message->setVolunteer($volunteer);
        $message->setSent(true);

        $this->emailProvider->expects($this->never())->method('send');

        $reflection = new \ReflectionMethod(Sender::class, 'sendEmail');
        $reflection->setAccessible(true);
        $reflection->invoke($this->sender, $message);
    }

    public function testSendEmailHandlesException()
    {
        $volunteer = $this->createVolunteer('+33612345678', true, 'user@example.com', true);
        $communication = $this->createCommunication(Communication::TYPE_EMAIL);

        $message = new Message();
        $message->setCommunication($communication);
        $message->setVolunteer($volunteer);

        $this->formatter->method('formatTextEmailContent')->willReturn('text');
        $this->formatter->method('formatHtmlEmailContent')->willReturn('<html>');

        $this->emailProvider->method('send')->willThrowException(new \Exception('SMTP error'));

        $this->messageManager->expects($this->once())->method('save');

        $reflection = new \ReflectionMethod(Sender::class, 'sendEmail');
        $reflection->setAccessible(true);
        $reflection->invoke($this->sender, $message);

        $this->assertSame('SMTP error', $message->getError());
        $this->assertFalse($message->isSent());
    }

    // --- Constants test ---

    public function testPauseConstants()
    {
        $this->assertSame(500000, Sender::PAUSE_SMS);
        $this->assertSame(200000, Sender::PAUSE_CALL);
        $this->assertSame(100000, Sender::PAUSE_EMAIL);
    }
}
