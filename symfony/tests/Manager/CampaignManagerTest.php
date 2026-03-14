<?php

namespace App\Tests\Manager;

use App\Entity\Campaign;
use App\Entity\Communication;
use App\Entity\Message;
use App\Manager\CampaignManager;
use App\Tests\Fixtures\DataFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CampaignManagerTest extends KernelTestCase
{
    /** @var CampaignManager */
    private $campaignManager;

    /** @var DataFixtures */
    private $fixtures;

    /** @var EntityManagerInterface */
    private $em;

    protected function setUp() : void
    {
        self::bootKernel();

        $this->campaignManager = self::$container->get(CampaignManager::class);
        $this->em = self::$container->get('doctrine.orm.entity_manager');
        $this->fixtures = new DataFixtures(
            $this->em,
            self::$container->get('security.password_encoder')
        );
    }

    // ──────────────────────────────────────────────
    // postponeExpiration
    // ──────────────────────────────────────────────

    public function testPostponeExpirationExtendsFutureExpiration()
    {
        $campaign = $this->fixtures->createCampaign('Postpone Future');

        // Set expiration to 2 days from now
        $future = (new \DateTime())->modify('+2 days');
        $campaign->setExpiresAt($future);
        $this->em->persist($campaign);
        $this->em->flush();

        $oldExpiration = $campaign->getExpiresAt()->getTimestamp();

        $this->campaignManager->postponeExpiration($campaign);

        $this->em->clear();
        $refreshed = $this->em->getRepository(Campaign::class)->find($campaign->getId());

        // New expiration should be old + DEFAULT_EXPIRATION
        $expectedMin = $oldExpiration + Campaign::DEFAULT_EXPIRATION - 5;
        $expectedMax = $oldExpiration + Campaign::DEFAULT_EXPIRATION + 5;
        $newTimestamp = $refreshed->getExpiresAt()->getTimestamp();

        $this->assertGreaterThanOrEqual($expectedMin, $newTimestamp);
        $this->assertLessThanOrEqual($expectedMax, $newTimestamp);
    }

    public function testPostponeExpirationResetsExpiredCampaign()
    {
        $campaign = $this->fixtures->createCampaign('Postpone Expired');

        // Set expiration in the past
        $past = (new \DateTime())->modify('-3 days');
        $campaign->setExpiresAt($past);
        $this->em->persist($campaign);
        $this->em->flush();

        $this->campaignManager->postponeExpiration($campaign);

        $this->em->clear();
        $refreshed = $this->em->getRepository(Campaign::class)->find($campaign->getId());

        // When expired, new expiration should be now() + DEFAULT_EXPIRATION
        $expected = time() + Campaign::DEFAULT_EXPIRATION;
        $actual = $refreshed->getExpiresAt()->getTimestamp();

        // Allow a 10 second tolerance
        $this->assertGreaterThanOrEqual($expected - 10, $actual);
        $this->assertLessThanOrEqual($expected + 10, $actual);
    }

    public function testPostponeExpirationPersistsToDatabase()
    {
        $campaign = $this->fixtures->createCampaign('Postpone Persist');

        $originalExpiration = $campaign->getExpiresAt()->getTimestamp();

        $this->campaignManager->postponeExpiration($campaign);

        $this->em->clear();
        $refreshed = $this->em->getRepository(Campaign::class)->find($campaign->getId());

        // The new expiration should be different from the original
        $this->assertNotEquals($originalExpiration, $refreshed->getExpiresAt()->getTimestamp());
    }

    // ──────────────────────────────────────────────
    // canReopenCampaign
    // ──────────────────────────────────────────────

    public function testCanReopenCampaignReturnsTrueForCampaignWithNoPrefixConflicts()
    {
        $setup = $this->fixtures->createFullCampaign('reopen1@test.com');
        $campaign = $setup['campaign'];

        // Close the campaign so it becomes inactive
        $campaign->setActive(false);
        $this->em->persist($campaign);
        $this->em->flush();

        // Since there is no other active campaign using the same prefixes, it should be reopenable
        $result = $this->campaignManager->canReopenCampaign($campaign);

        $this->assertTrue($result);
    }

    public function testCanReopenCampaignReturnsFalseWhenPrefixConflict()
    {
        // Create two campaigns targeting the same volunteer with the same prefix
        $user = $this->fixtures->createRawUser('reopenconflict@test.com');
        $volunteer = $this->fixtures->createVolunteer($user, 'VOL-REOPEN-CONFLICT');
        $structure = $this->fixtures->createStructure('STRUCT-REOPEN', 'EXT-REOPEN');
        $this->fixtures->assignUserToStructure($user, $structure);
        $this->fixtures->assignVolunteerToStructure($volunteer, $structure);

        // Campaign 1: active, with prefix A
        $campaign1 = $this->fixtures->createCampaign('Active Campaign', Campaign::TYPE_GREEN, true);
        $comm1 = $this->fixtures->createCommunication($campaign1);
        $msg1 = $this->fixtures->createMessage($comm1, $volunteer);
        $msg1->setPrefix('A');
        $this->em->persist($msg1);
        $this->em->flush();

        // Campaign 2: inactive, same prefix A for same volunteer
        $campaign2 = $this->fixtures->createCampaign('Inactive Campaign', Campaign::TYPE_GREEN, false);
        $comm2 = $this->fixtures->createCommunication($campaign2);
        $msg2 = $this->fixtures->createMessage($comm2, $volunteer);
        $msg2->setPrefix('A');
        $this->em->persist($msg2);
        $this->em->flush();

        // campaign2 should NOT be reopenable because prefix A is used in active campaign1
        $result = $this->campaignManager->canReopenCampaign($campaign2);

        $this->assertFalse($result);
    }

    public function testCanReopenCampaignReturnsTrueWhenNoCommunicationsHaveMessages()
    {
        // A campaign with an empty communication (no messages) should be reopenable
        $campaign = $this->fixtures->createCampaign('No Msgs', Campaign::TYPE_GREEN, false);
        $comm = $this->fixtures->createCommunication($campaign);

        $result = $this->campaignManager->canReopenCampaign($campaign);

        $this->assertTrue($result);
    }

    // ──────────────────────────────────────────────
    // getHash
    // ──────────────────────────────────────────────

    public function testGetHashReturnsSha1String()
    {
        $setup = $this->fixtures->createFullCampaign('hash1@test.com');
        $campaign = $setup['campaign'];

        $hash = $this->campaignManager->getHash($campaign->getId());

        $this->assertNotEmpty($hash);
        $this->assertEquals(40, strlen($hash), 'Hash should be a 40-char SHA1 string');
    }

    public function testGetHashReturnsSameValueForUnchangedCampaign()
    {
        $setup = $this->fixtures->createFullCampaign('hash2@test.com');
        $campaign = $setup['campaign'];

        $hash1 = $this->campaignManager->getHash($campaign->getId());
        $hash2 = $this->campaignManager->getHash($campaign->getId());

        $this->assertEquals($hash1, $hash2, 'Hash should be stable for unchanged campaign');
    }

    public function testGetHashChangesWhenAnswerAdded()
    {
        $setup = $this->fixtures->createFullCampaign('hash3@test.com');
        $campaign = $setup['campaign'];
        $message = $setup['message'];

        $hashBefore = $this->campaignManager->getHash($campaign->getId());

        // Add an answer
        $this->fixtures->createAnswer($message, 'Test answer');

        $hashAfter = $this->campaignManager->getHash($campaign->getId());

        $this->assertNotEquals($hashBefore, $hashAfter, 'Hash should change when answers are added');
    }

    public function testGetHashChangesWhenNoteUpdated()
    {
        $setup = $this->fixtures->createFullCampaign('hash4@test.com');
        $campaign = $setup['campaign'];

        $hashBefore = $this->campaignManager->getHash($campaign->getId());

        // Update notes
        $campaign->setNotes('New note');
        $campaign->setNotesUpdatedAt(new \DateTime());
        $this->em->persist($campaign);
        $this->em->flush();

        $hashAfter = $this->campaignManager->getHash($campaign->getId());

        $this->assertNotEquals($hashBefore, $hashAfter, 'Hash should change when notes are updated');
    }

    // ──────────────────────────────────────────────
    // launchNewCampaign (partial: testing is limited because
    // it requires CommunicationManager, processors, etc.)
    // We test the basic guard: null volunteer returns null
    // ──────────────────────────────────────────────

    public function testLaunchNewCampaignReturnsNullWhenNoVolunteer()
    {
        $user = $this->fixtures->createRawUser('novolunteer@test.com');

        // Set up the token storage with a user that has NO volunteer
        $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
            $user, null, 'main', $user->getRoles()
        );
        self::$container->get('security.token_storage')->setToken($token);

        $trigger = new \App\Form\Model\SmsTrigger();
        $campaignModel = new \App\Form\Model\Campaign($trigger);
        $campaignModel->label = 'Test';
        $campaignModel->type = Campaign::TYPE_GREEN;

        $result = $this->campaignManager->launchNewCampaign($campaignModel);

        $this->assertNull($result);
    }
}
