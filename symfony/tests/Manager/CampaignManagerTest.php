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

        $this->campaignManager = self::getContainer()->get(CampaignManager::class);
        $this->em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->fixtures = new DataFixtures(
            $this->em,
            self::getContainer()->get('security.password_hasher')
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

        // Reset lastActivityAt to a known past value so the hash changes detectably
        $this->em->createQuery(
            'UPDATE App\Entity\Campaign c SET c.lastActivityAt = :past WHERE c.id = :id'
        )
            ->setParameter('past', new \DateTime('2020-01-01'))
            ->setParameter('id', $campaign->getId())
            ->execute();

        $hashBefore = $this->campaignManager->getHash($campaign->getId());

        // Simulate what CommunicationActivitySubscriber does: update lastActivityAt
        $this->em->createQuery(
            'UPDATE App\Entity\Campaign c SET c.lastActivityAt = :now WHERE c.id = :id'
        )
            ->setParameter('now', new \DateTime())
            ->setParameter('id', $campaign->getId())
            ->execute();

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
    //
    // (removed testLaunchNewCampaignReturnsNullWhenNoVolunteer: an operator no
    // longer needs to be a volunteer to launch a campaign — the author is the
    // User directly, so that early-return guard was dropped.)
    // ──────────────────────────────────────────────

    /**
     * Happy-path smoke test for the full campaign-creation pipeline.
     *
     * Submitting a new SMS campaign was throwing
     * `NotFoundHttpException("New communication has no message")` at
     * CampaignManager:119 because the trigger audience was being passed
     * through with a volunteer that ended up filtered out. This test
     * builds a minimal but realistic SMS trigger and asserts the campaign
     * is created with at least one message.
     */
    public function testLaunchNewSmsCampaignCreatesCommunicationWithMessages()
    {
        // Create a user, a volunteer for that user, and a structure
        // they both belong to so voter access lines up.
        $setup     = $this->fixtures->createUserWithVolunteerAndStructure(
            'sms_launch@test.com',
            false,
            'VOL-SMS-LAUNCH',
            'STRUCT SMS LAUNCH',
            'EXT-STRUCT-SMS-LAUNCH'
        );
        $user      = $setup['user'];
        $volunteer = $setup['volunteer'];
        $structure = $setup['structure'];

        // Add a second, distinct volunteer to be the audience target so the
        // sender's own volunteer is not the receiver — this mirrors what
        // production does and is enough to surface the bug.
        $targetVolunteer = $this->fixtures->createStandaloneVolunteer('VOL-SMS-TARGET', 'target-sms@test.com');
        $this->fixtures->assignVolunteerToStructure($targetVolunteer, $structure);

        // Authenticate as the user so the manager's $security->getUser() resolves.
        $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );
        self::getContainer()->get('security.token_storage')->setToken($token);

        // Build a minimal but valid SMS trigger: one volunteer in the audience,
        // a simple message, default empty answers (free-form). Use
        // AudienceType::createEmptyData to get all required audience keys.
        $trigger = new \App\Form\Model\SmsTrigger();
        $trigger->setLabel('Smoke test');
        $trigger->setLanguage('fr');
        $trigger->setMessage('Hello volunteer, please respond.');
        $trigger->setAudience(\App\Form\Type\AudienceType::createEmptyData([
            'volunteers' => [$targetVolunteer->getId()],
        ]));

        $campaignModel        = new \App\Form\Model\Campaign($trigger);
        $campaignModel->label = 'SMS launch test';
        $campaignModel->type  = Campaign::TYPE_GREEN;

        $result = $this->campaignManager->launchNewCampaign($campaignModel);

        $this->assertNotNull($result, 'launchNewCampaign should return a Campaign entity for a valid SMS trigger');
        $this->assertNotNull($result->getId(), 'Campaign should be persisted (have an ID)');

        $this->em->clear();
        $persistedCampaign = $this->em->getRepository(Campaign::class)->find($result->getId());

        $this->assertCount(1, $persistedCampaign->getCommunications(), 'Campaign should have exactly one communication after launch');

        /** @var Communication $communication */
        $communication = $persistedCampaign->getCommunications()->first();
        $this->assertGreaterThanOrEqual(1, $communication->getMessageCount(), 'Communication should have at least one message — empty messages mean the audience pipeline dropped every recipient');
    }

    /**
     * Mirrors the most common UI path: user toggles "select all badges",
     * picks a structure (no explicit volunteer IDs or badges), and submits.
     * The audience pipeline must resolve the structure to its volunteers
     * via `getVolunteerListInStructures`. A regression here surfaces as
     * "New communication has no message".
     */
    public function testLaunchNewSmsCampaignViaStructureWithAllBadges()
    {
        $setup     = $this->fixtures->createUserWithVolunteerAndStructure(
            'sms_struct@test.com',
            false,
            'VOL-SMS-STRUCT',
            'STRUCT SMS ALLBADGES',
            'EXT-STRUCT-SMS-ALL'
        );
        $user      = $setup['user'];
        $structure = $setup['structure'];

        $targetVolunteer = $this->fixtures->createStandaloneVolunteer('VOL-SMS-T2', 'target-sms2@test.com');
        $this->fixtures->assignVolunteerToStructure($targetVolunteer, $structure);

        $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );
        self::getContainer()->get('security.token_storage')->setToken($token);

        $trigger = new \App\Form\Model\SmsTrigger();
        $trigger->setLabel('All-badges structure test');
        $trigger->setLanguage('fr');
        $trigger->setMessage('Hello structure.');
        $trigger->setAudience(\App\Form\Type\AudienceType::createEmptyData([
            'structures_global' => [$structure->getId()],
            'badges_all'        => true,
        ]));

        $campaignModel        = new \App\Form\Model\Campaign($trigger);
        $campaignModel->label = 'SMS structure-all test';
        $campaignModel->type  = Campaign::TYPE_GREEN;

        $result = $this->campaignManager->launchNewCampaign($campaignModel);

        $this->assertNotNull($result);
        /** @var Communication $communication */
        $communication = $result->getCommunications()->first();
        $this->assertNotNull($communication, 'Campaign must have a communication');
        $this->assertGreaterThanOrEqual(
            1,
            $communication->getMessageCount(),
            'When the user picks a structure and toggles "select all badges", every reachable volunteer in that structure must receive a message.'
        );
    }

    /**
     * Mirrors a realistic "by structure, no badge filter" submission: the
     * user picks a structure but does NOT toggle the "all badges" switch
     * (the default state). The audience pipeline routes this through
     * `getVolunteerListInStructuresHavingBadges($structureIds, [])` — an
     * empty badge list, which previously meant "include every volunteer"
     * but now means "match no badges = no volunteers".
     *
     * This is a strong candidate for the production failure mode of
     * "New communication has no message" with a populated audience.
     */
    public function testLaunchNewSmsCampaignViaStructureWithoutBadgeFilter()
    {
        $setup     = $this->fixtures->createUserWithVolunteerAndStructure(
            'sms_struct_nobadge@test.com',
            false,
            'VOL-SMS-NOBADGE',
            'STRUCT SMS NOBADGE',
            'EXT-STRUCT-SMS-NOBADGE'
        );
        $user      = $setup['user'];
        $structure = $setup['structure'];

        $targetVolunteer = $this->fixtures->createStandaloneVolunteer('VOL-SMS-T3', 'target-sms3@test.com');
        $this->fixtures->assignVolunteerToStructure($targetVolunteer, $structure);

        $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );
        self::getContainer()->get('security.token_storage')->setToken($token);

        $trigger = new \App\Form\Model\SmsTrigger();
        $trigger->setLabel('Structure-only, no badge filter');
        $trigger->setLanguage('fr');
        $trigger->setMessage('Hello structure-no-badge.');
        $trigger->setAudience(\App\Form\Type\AudienceType::createEmptyData([
            'structures_global' => [$structure->getId()],
            'badges_all'        => false, // explicit: user did NOT toggle "all badges"
        ]));

        $campaignModel        = new \App\Form\Model\Campaign($trigger);
        $campaignModel->label = 'SMS structure-no-badge';
        $campaignModel->type  = Campaign::TYPE_GREEN;

        $result = $this->campaignManager->launchNewCampaign($campaignModel);

        $this->assertNotNull(
            $result,
            'Submitting an SMS campaign targeting a structure should include every volunteer in that structure even when no badge filter is set — without this, the user sees "New communication has no message" with a perfectly valid audience.'
        );
        /** @var Communication $communication */
        $communication = $result->getCommunications()->first();
        $this->assertGreaterThanOrEqual(
            1,
            $communication->getMessageCount(),
            'A structure without a badge filter should resolve to every volunteer in that structure.'
        );
    }

    /**
     * Mirrors the "Test on me" submit: the user clicks the test button which
     * sets `audience.test_on_me = true`. The audience pipeline must route
     * this to the current user's volunteer regardless of the other audience
     * fields. A regression here also surfaces as
     * "New communication has no message".
     */
    public function testLaunchNewSmsCampaignWithTestOnMe()
    {
        $setup     = $this->fixtures->createUserWithVolunteerAndStructure(
            'sms_test_on_me@test.com',
            false,
            'VOL-SMS-TOM',
            'STRUCT SMS TOM',
            'EXT-STRUCT-SMS-TOM'
        );
        $user = $setup['user'];

        $token = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );
        self::getContainer()->get('security.token_storage')->setToken($token);

        $trigger = new \App\Form\Model\SmsTrigger();
        $trigger->setLabel('Test on me');
        $trigger->setLanguage('fr');
        $trigger->setMessage('Hello me.');
        $trigger->setAudience(\App\Form\Type\AudienceType::createEmptyData([
            'test_on_me' => true,
        ]));

        $campaignModel        = new \App\Form\Model\Campaign($trigger);
        $campaignModel->label = 'SMS test-on-me';
        $campaignModel->type  = Campaign::TYPE_GREEN;

        $result = $this->campaignManager->launchNewCampaign($campaignModel);

        $this->assertNotNull($result);
        /** @var Communication $communication */
        $communication = $result->getCommunications()->first();
        $this->assertNotNull($communication);
        $this->assertSame(
            1,
            $communication->getMessageCount(),
            'A "test on me" SMS must produce exactly one message addressed to the current user\'s volunteer.'
        );
    }
}
