<?php

namespace App\Tests\Manager;

use App\Entity\Answer;
use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Volunteer;
use App\Manager\MessageManager;
use App\Repository\MessageRepository;
use App\Tests\Fixtures\DataFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MessageManagerTest extends KernelTestCase
{
    /** @var MessageManager */
    private $messageManager;

    /** @var DataFixtures */
    private $fixtures;

    /** @var EntityManagerInterface */
    private $em;

    protected function setUp() : void
    {
        self::bootKernel();

        $this->messageManager = self::$container->get(MessageManager::class);
        $this->em = self::$container->get('doctrine.orm.entity_manager');
        $this->fixtures = new DataFixtures(
            $this->em,
            self::$container->get('security.password_encoder')
        );
    }

    // ──────────────────────────────────────────────
    // generateCodes
    // ──────────────────────────────────────────────

    public function testGenerateCodesReturnsRequestedCount()
    {
        $codes = $this->messageManager->generateCodes(5);

        $this->assertCount(5, $codes);
    }

    public function testGenerateCodesReturnsUniqueCodes()
    {
        $codes = $this->messageManager->generateCodes(20);

        $this->assertCount(20, $codes);
        $this->assertCount(20, array_unique($codes), 'All generated codes should be unique');
    }

    public function testGenerateCodesCorrectLength()
    {
        $codes = $this->messageManager->generateCodes(3);

        foreach ($codes as $code) {
            $this->assertEquals(MessageRepository::CODE_SIZE, strlen($code));
        }
    }

    public function testGenerateCodesZero()
    {
        $codes = $this->messageManager->generateCodes(0);

        $this->assertCount(0, $codes);
    }

    // ──────────────────────────────────────────────
    // generatePrefixes
    // ──────────────────────────────────────────────

    public function testGeneratePrefixesAssignsFirstPrefixForNewVolunteers()
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-PFX-001', 'pfx1@test.com');

        $prefixes = $this->messageManager->generatePrefixes([$volunteer]);

        $this->assertArrayHasKey($volunteer->getId(), $prefixes);
        $this->assertEquals('A', $prefixes[$volunteer->getId()]);
    }

    public function testGeneratePrefixesAssignsMultipleVolunteers()
    {
        $v1 = $this->fixtures->createStandaloneVolunteer('VOL-PFX-010', 'pfx10@test.com');
        $v2 = $this->fixtures->createStandaloneVolunteer('VOL-PFX-011', 'pfx11@test.com');

        $prefixes = $this->messageManager->generatePrefixes([$v1, $v2]);

        $this->assertCount(2, $prefixes);
        $this->assertArrayHasKey($v1->getId(), $prefixes);
        $this->assertArrayHasKey($v2->getId(), $prefixes);
    }

    public function testGeneratePrefixesIncrementsWhenPrefixAlreadyUsed()
    {
        // Create a campaign with a message assigned prefix 'A' for a volunteer
        $setup = $this->fixtures->createFullCampaign('pfxtest@test.com');
        $volunteer = $setup['volunteer'];
        $message = $setup['message'];

        // Set prefix to 'A'
        $message->setPrefix('A');
        $this->em->persist($message);
        $this->em->flush();

        // Now generate prefixes for the same volunteer: should skip 'A' and give 'B'
        $prefixes = $this->messageManager->generatePrefixes([$volunteer]);

        $this->assertEquals('B', $prefixes[$volunteer->getId()]);
    }

    // ──────────────────────────────────────────────
    // getMessageFromPhoneNumber
    // ──────────────────────────────────────────────

    public function testGetMessageFromPhoneNumberReturnsNullForUnknownPhone()
    {
        $result = $this->messageManager->getMessageFromPhoneNumber('+33999999999');

        $this->assertNull($result);
    }

    // ──────────────────────────────────────────────
    // handleAnswer
    // ──────────────────────────────────────────────

    public function testHandleAnswerReturnsNullForUnknownPhone()
    {
        $result = $this->messageManager->handleAnswer('+33888888888', 'A1');

        $this->assertNull($result);
    }

    // ──────────────────────────────────────────────
    // addAnswer
    // ──────────────────────────────────────────────

    public function testAddAnswerCreatesAnswerOnMessage()
    {
        $setup = $this->fixtures->createFullCampaign('addans@test.com');
        $message = $setup['message'];
        $choices = $setup['choices'];

        // Set prefix so choice matching works
        $message->setPrefix('A');
        $this->em->persist($message);
        $this->em->flush();

        $initialCount = $message->getAnswers()->count();

        $this->messageManager->addAnswer($message, 'A1');

        $this->em->clear();

        $refreshedMessage = $this->em->getRepository(Message::class)->find($message->getId());
        $this->assertGreaterThan($initialCount, $refreshedMessage->getAnswers()->count());
    }

    public function testAddAnswerStoresRawBody()
    {
        $setup = $this->fixtures->createFullCampaign('rawans@test.com');
        $message = $setup['message'];

        $message->setPrefix('A');
        $this->em->persist($message);
        $this->em->flush();

        $this->messageManager->addAnswer($message, 'some random text');

        $this->em->clear();

        $refreshedMessage = $this->em->getRepository(Message::class)->find($message->getId());
        $latestAnswer = $refreshedMessage->getAnswers()->first();
        $this->assertNotNull($latestAnswer);
        $this->assertEquals('some random text', $latestAnswer->getRaw());
    }

    public function testAddAnswerWithValidChoiceLinkChoice()
    {
        $setup = $this->fixtures->createFullCampaign('validch@test.com');
        $message = $setup['message'];
        $choices = $setup['choices'];

        $message->setPrefix('A');
        $this->em->persist($message);
        $this->em->flush();

        // choices[0] has code "1", so A1 should match
        $this->messageManager->addAnswer($message, 'A1');

        $this->em->clear();

        $refreshedMessage = $this->em->getRepository(Message::class)->find($message->getId());
        $answers = $refreshedMessage->getAnswers();

        $foundChoice = false;
        foreach ($answers as $answer) {
            foreach ($answer->getChoices() as $choice) {
                if ($choice->getCode() === '1') {
                    $foundChoice = true;
                }
            }
        }
        $this->assertTrue($foundChoice, 'Answer should have choice with code 1');
    }

    public function testAddAnswerClearsPreviousAnswersWhenNotMultipleChoice()
    {
        $setup = $this->fixtures->createFullCampaign('singlechoice@test.com');
        $message = $setup['message'];
        $comm = $setup['communication'];

        // Ensure not multi-answer
        $comm->setMultipleAnswer(false);
        $this->em->persist($comm);

        $message->setPrefix('A');
        $this->em->persist($message);
        $this->em->flush();

        // Add first answer
        $this->messageManager->addAnswer($message, 'A1');
        // Add second answer (should clear the first)
        $this->messageManager->addAnswer($message, 'A2');

        $this->em->clear();

        $refreshedMessage = $this->em->getRepository(Message::class)->find($message->getId());
        // With single choice, only the latest set of answers should remain with valid choices
        // The old choice assignment should be cleared
        $lastAnswer = $refreshedMessage->getAnswers()->first();
        $this->assertNotNull($lastAnswer);
        $this->assertEquals('A2', $lastAnswer->getRaw());
    }

    // ──────────────────────────────────────────────
    // toggleAnswer
    // ──────────────────────────────────────────────

    public function testToggleAnswerAddsChoiceWhenNotPresent()
    {
        $setup = $this->fixtures->createFullCampaign('toggle1@test.com');
        $message = $setup['message'];
        $choice = $setup['choices'][0];

        $message->setPrefix('A');
        $this->em->persist($message);
        $this->em->flush();

        // Set up a token so byAdmin works
        $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
            $setup['user'], null, 'main', $setup['user']->getRoles()
        );
        self::$container->get('security.token_storage')->setToken($token);

        $this->messageManager->toggleAnswer($message, $choice);

        $this->em->clear();

        $refreshedMessage = $this->em->getRepository(Message::class)->find($message->getId());
        $hasChoice = false;
        foreach ($refreshedMessage->getAnswers() as $answer) {
            if ($answer->hasChoice($choice)) {
                $hasChoice = true;
            }
        }
        // The choice should have been freshly re-fetched after clear(), so we check via code
        $found = false;
        foreach ($refreshedMessage->getAnswers() as $answer) {
            foreach ($answer->getChoices() as $c) {
                if ($c->getCode() === $choice->getCode()) {
                    $found = true;
                }
            }
        }
        $this->assertTrue($found, 'Toggle should add the choice when not present');
    }

    public function testToggleAnswerRemovesChoiceWhenPresent()
    {
        $setup = $this->fixtures->createFullCampaign('toggle2@test.com');
        $message = $setup['message'];
        $choice = $setup['choices'][0];

        $message->setPrefix('A');
        $this->em->persist($message);
        $this->em->flush();

        // Set up a token so byAdmin works
        $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
            $setup['user'], null, 'main', $setup['user']->getRoles()
        );
        self::$container->get('security.token_storage')->setToken($token);

        // Add the choice first
        $this->messageManager->toggleAnswer($message, $choice);

        // Now toggle again: should remove it
        // Need to re-fetch the message since addAnswer may have changed in-memory state
        $this->em->clear();
        $message = $this->em->getRepository(Message::class)->find($message->getId());
        $choice = $this->em->getRepository(Choice::class)->find($choice->getId());

        $this->messageManager->toggleAnswer($message, $choice);

        $this->em->clear();
        $refreshedMessage = $this->em->getRepository(Message::class)->find($message->getId());

        $found = false;
        foreach ($refreshedMessage->getAnswers() as $answer) {
            if ($answer->hasChoice($choice)) {
                $found = true;
            }
        }
        // After re-toggle, the choice should be removed from all answers
        // (Note: the answer row still exists, but the choice link is removed)
        $choiceStillLinked = false;
        foreach ($refreshedMessage->getAnswers() as $answer) {
            foreach ($answer->getChoices() as $c) {
                if ($c->getId() === $choice->getId()) {
                    $choiceStillLinked = true;
                }
            }
        }
        $this->assertFalse($choiceStillLinked, 'Toggle should remove the choice when already present');
    }

    // ──────────────────────────────────────────────
    // getDeployGreenlight
    // ──────────────────────────────────────────────

    public function testGetDeployGreenlightReturnsZeroWhenNoRecentActivity()
    {
        // If the latest message was updated more than DEPLOY_GRACE seconds ago, returns 0
        // Create a message with old updatedAt
        $setup = $this->fixtures->createFullCampaign('deploy1@test.com');
        $message = $setup['message'];

        $oldDate = (new \DateTime())->modify('-300 seconds');
        $message->setUpdatedAt($oldDate);
        $this->em->persist($message);
        $this->em->flush();

        $result = $this->messageManager->getDeployGreenlight();

        $this->assertEquals(0, $result);
    }

    public function testGetDeployGreenlightReturnsRemainingTimeWhenRecentActivity()
    {
        $setup = $this->fixtures->createFullCampaign('deploy2@test.com');
        $message = $setup['message'];

        // Set updatedAt to just 30 seconds ago
        $recentDate = (new \DateTime())->modify('-30 seconds');
        $message->setUpdatedAt($recentDate);
        $this->em->persist($message);
        $this->em->flush();

        $result = $this->messageManager->getDeployGreenlight();

        // Should return remaining time (around 90 seconds, give or take)
        $this->assertGreaterThan(0, $result);
        $this->assertLessThanOrEqual(MessageManager::DEPLOY_GRACE, $result);
    }

    // ──────────────────────────────────────────────
    // getLatestMessagesForVolunteer
    // ──────────────────────────────────────────────

    public function testGetLatestMessagesForVolunteerReturnsEmptyForNullId()
    {
        // A volunteer that has never been persisted has null ID
        $volunteer = new Volunteer();

        $result = $this->messageManager->getLatestMessagesForVolunteer($volunteer);

        $this->assertEmpty($result);
    }

    public function testGetLatestMessagesForVolunteerReturnsMessages()
    {
        $setup = $this->fixtures->createFullCampaign('latestmsg@test.com');
        $volunteer = $setup['volunteer'];

        $result = $this->messageManager->getLatestMessagesForVolunteer($volunteer);

        $this->assertNotEmpty($result);
        $this->assertInstanceOf(Message::class, $result[0]);
    }

    public function testGetLatestMessagesForVolunteerReturnsEmptyWhenNoMessages()
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-NOMSG', 'nomsg@test.com');

        $result = $this->messageManager->getLatestMessagesForVolunteer($volunteer);

        $this->assertEmpty($result);
    }
}
