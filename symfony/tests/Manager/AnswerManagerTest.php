<?php

namespace App\Tests\Manager;

use App\Entity\Answer;
use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Volunteer;
use App\Manager\AnswerManager;
use App\Manager\MessageManager;
use App\Tests\Fixtures\DataFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AnswerManagerTest extends KernelTestCase
{
    /** @var AnswerManager */
    private $answerManager;

    /** @var DataFixtures */
    private $fixtures;

    /** @var EntityManagerInterface */
    private $em;

    protected function setUp() : void
    {
        self::bootKernel();

        $this->answerManager = self::$container->get(AnswerManager::class);
        $this->em = self::$container->get('doctrine.orm.entity_manager');
        $this->fixtures = new DataFixtures(
            $this->em,
            self::$container->get('security.password_encoder')
        );
    }

    // ──────────────────────────────────────────────
    // handleSpecialAnswers
    // ──────────────────────────────────────────────

    public function testHandleSpecialAnswersDoesNothingForNonStopMessage()
    {
        $setup = $this->fixtures->createFullCampaign('special1@test.com');
        $message = $setup['message'];
        $volunteer = $setup['volunteer'];

        // Verify optin is true before
        $this->assertTrue($volunteer->isPhoneNumberOptin());

        // Body is not a stop word
        $this->answerManager->handleSpecialAnswers($message, 'A1');

        // Optin should remain true
        $this->em->clear();
        $refreshedVolunteer = $this->em->getRepository(Volunteer::class)->find($volunteer->getId());
        $this->assertTrue($refreshedVolunteer->isPhoneNumberOptin());
    }

    public function testHandleSpecialAnswersWithStopWordOptOutsVolunteer()
    {
        $setup = $this->fixtures->createFullCampaign('special2@test.com');
        $message = $setup['message'];
        $volunteer = $setup['volunteer'];

        // The communication must be SMS for stop words to work
        $this->assertTrue($message->getCommunication()->isSms());

        // Volunteer must be opted in
        $this->assertTrue($volunteer->isPhoneNumberOptin());

        // Note: handleSpecialAnswers calls sendSms which requires SMSProvider.
        // In the test env, the SMSProvider may be a fake. We test the optout logic.
        $this->answerManager->handleSpecialAnswers($message, 'STOP');

        $this->em->clear();
        $refreshedVolunteer = $this->em->getRepository(Volunteer::class)->find($volunteer->getId());
        $this->assertFalse($refreshedVolunteer->isPhoneNumberOptin());
    }

    public function testHandleSpecialAnswersWithArretWordOptOutsVolunteer()
    {
        $setup = $this->fixtures->createFullCampaign('special3@test.com');
        $message = $setup['message'];
        $volunteer = $setup['volunteer'];

        $this->answerManager->handleSpecialAnswers($message, 'ARRET');

        $this->em->clear();
        $refreshedVolunteer = $this->em->getRepository(Volunteer::class)->find($volunteer->getId());
        $this->assertFalse($refreshedVolunteer->isPhoneNumberOptin());
    }

    public function testHandleSpecialAnswersIgnoresStopWhenAlreadyOptedOut()
    {
        $setup = $this->fixtures->createFullCampaign('special4@test.com');
        $message = $setup['message'];
        $volunteer = $setup['volunteer'];

        // Opt out the volunteer first
        $volunteer->setPhoneNumberOptin(false);
        $this->em->persist($volunteer);
        $this->em->flush();

        // Count answers before
        $answerCountBefore = $message->getAnswers()->count();

        // STOP should be ignored since volunteer is already opted out
        $this->answerManager->handleSpecialAnswers($message, 'STOP');

        // No new answer/SMS should have been sent (the method returns early)
        $this->em->clear();
        $refreshedMessage = $this->em->getRepository(Message::class)->find($message->getId());
        $this->assertEquals($answerCountBefore, $refreshedMessage->getAnswers()->count());
    }

    public function testHandleSpecialAnswersIgnoresStopForNonSmsCommunication()
    {
        $setup = $this->fixtures->createFullCampaign(
            'special5@test.com',
            false,
            Communication::TYPE_EMAIL
        );
        $message = $setup['message'];
        $volunteer = $setup['volunteer'];

        $this->assertTrue($volunteer->isPhoneNumberOptin());

        // For email, STOP should not trigger opt out
        $this->answerManager->handleSpecialAnswers($message, 'STOP');

        $this->em->clear();
        $refreshedVolunteer = $this->em->getRepository(Volunteer::class)->find($volunteer->getId());
        $this->assertTrue($refreshedVolunteer->isPhoneNumberOptin());
    }

    public function testHandleSpecialAnswersCaseInsensitiveStop()
    {
        $setup = $this->fixtures->createFullCampaign('special6@test.com');
        $message = $setup['message'];
        $volunteer = $setup['volunteer'];

        // Stop in lower case
        $this->answerManager->handleSpecialAnswers($message, 'stop');

        $this->em->clear();
        $refreshedVolunteer = $this->em->getRepository(Volunteer::class)->find($volunteer->getId());
        $this->assertFalse($refreshedVolunteer->isPhoneNumberOptin());
    }

    // ──────────────────────────────────────────────
    // sendSms
    // ──────────────────────────────────────────────

    public function testSendSmsCreatesAnswerOnMessage()
    {
        $setup = $this->fixtures->createFullCampaign('sendsms1@test.com');
        $message = $setup['message'];

        $initialCount = $message->getAnswers()->count();

        $this->answerManager->sendSms($message, 'Test SMS content');

        $this->em->clear();

        $refreshedMessage = $this->em->getRepository(Message::class)->find($message->getId());
        $this->assertGreaterThan($initialCount, $refreshedMessage->getAnswers()->count());
    }

    public function testSendSmsAnswerIsMarkedAsRobot()
    {
        $setup = $this->fixtures->createFullCampaign('sendsms2@test.com');
        $message = $setup['message'];

        // No authenticated user -> byAdmin = 'Robot'
        self::$container->get('security.token_storage')->setToken(null);

        $this->answerManager->sendSms($message, 'Auto message');

        $this->em->clear();

        $refreshedMessage = $this->em->getRepository(Message::class)->find($message->getId());
        $latestAnswer = $refreshedMessage->getAnswers()->first();

        $this->assertNotNull($latestAnswer);
        $this->assertEquals('Robot', $latestAnswer->getByAdmin());
    }

    public function testSendSmsAnswerIsMarkedWithCurrentUser()
    {
        $setup = $this->fixtures->createFullCampaign('sendsms3@test.com');
        $message = $setup['message'];
        $user = $setup['user'];

        // Set token with actual user
        $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
            $user, null, 'main', $user->getRoles()
        );
        self::$container->get('security.token_storage')->setToken($token);

        $this->answerManager->sendSms($message, 'Admin message');

        $this->em->clear();

        $refreshedMessage = $this->em->getRepository(Message::class)->find($message->getId());
        $latestAnswer = $refreshedMessage->getAnswers()->first();

        $this->assertNotNull($latestAnswer);
        $this->assertEquals($user->getUserIdentifier(), $latestAnswer->getByAdmin());
    }

    public function testSendSmsStoresRawContent()
    {
        $setup = $this->fixtures->createFullCampaign('sendsms4@test.com');
        $message = $setup['message'];

        $content = 'This is the SMS content to send';
        $this->answerManager->sendSms($message, $content);

        $this->em->clear();

        $refreshedMessage = $this->em->getRepository(Message::class)->find($message->getId());
        $latestAnswer = $refreshedMessage->getAnswers()->first();

        $this->assertNotNull($latestAnswer);
        $this->assertEquals($content, $latestAnswer->getRaw());
    }

    public function testSendSmsMarksAnswerAsUnclear()
    {
        $setup = $this->fixtures->createFullCampaign('sendsms5@test.com');
        $message = $setup['message'];

        $this->answerManager->sendSms($message, 'test');

        $this->em->clear();

        $refreshedMessage = $this->em->getRepository(Message::class)->find($message->getId());
        $latestAnswer = $refreshedMessage->getAnswers()->first();

        $this->assertNotNull($latestAnswer);
        $this->assertTrue($latestAnswer->isUnclear());
    }
}
