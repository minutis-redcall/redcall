<?php

namespace App\Tests\Security\Voter;

use App\Entity\Badge;
use App\Entity\Campaign;
use App\Entity\Communication;
use App\Security\Voter\CampaignVoter;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class CampaignVoterTest extends KernelTestCase
{
    private CampaignVoter $voter;
    private DataFixtures $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->voter = $container->get(CampaignVoter::class);
        $this->fixtures = new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    private function createToken($user): UsernamePasswordToken
    {
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        static::getContainer()->get('security.token_storage')->setToken($token);

        return $token;
    }

    private function em()
    {
        return static::getContainer()->get('doctrine.orm.entity_manager');
    }

    // ────────────────────────────────────────────────────────
    // supports()
    // ────────────────────────────────────────────────────────

    public function testSupportsReturnsTrueForCampaignOwnerAttribute(): void
    {
        $campaign = $this->fixtures->createCampaign('Supports Campaign');
        $rootUser = $this->fixtures->createRawUser('cv_sup_owner@test.com', 'password', true);
        $token = $this->createToken($rootUser);

        $result = $this->voter->vote($token, $campaign, [CampaignVoter::OWNER]);
        $this->assertNotEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsTrueForCampaignAccessAttribute(): void
    {
        $campaign = $this->fixtures->createCampaign('Supports Campaign 2');
        $rootUser = $this->fixtures->createRawUser('cv_sup_access@test.com', 'password', true);
        $token = $this->createToken($rootUser);

        $result = $this->voter->vote($token, $campaign, [CampaignVoter::ACCESS]);
        $this->assertNotEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForUnsupportedAttribute(): void
    {
        $campaign = $this->fixtures->createCampaign('Unsupported Attr Campaign');
        $rootUser = $this->fixtures->createRawUser('cv_sup_unsup@test.com', 'password', true);
        $token = $this->createToken($rootUser);

        $result = $this->voter->vote($token, $campaign, ['RANDOM_ATTR']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForNonCampaignSubject(): void
    {
        $rootUser = $this->fixtures->createRawUser('cv_nonsub@test.com', 'password', true);
        $token = $this->createToken($rootUser);
        $badge = $this->fixtures->createBadge('Not Campaign', 'NOT-CAMP-001');

        $result = $this->voter->vote($token, $badge, [CampaignVoter::ACCESS]);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForNullSubject(): void
    {
        $rootUser = $this->fixtures->createRawUser('cv_null@test.com', 'password', true);
        $token = $this->createToken($rootUser);

        $result = $this->voter->vote($token, null, [CampaignVoter::ACCESS]);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    // ────────────────────────────────────────────────────────
    // Root user
    // ────────────────────────────────────────────────────────

    public function testRootUserIsGrantedForAnyCampaign(): void
    {
        $rootUser = $this->fixtures->createRawUser('cv_root@test.com', 'password', true);
        $rootUser->setIsRoot(true);
        $this->em()->persist($rootUser);
        $this->em()->flush();

        $token = $this->createToken($rootUser);
        $campaign = $this->fixtures->createCampaign('Root Campaign');

        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $campaign, [CampaignVoter::OWNER])
        );
        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $campaign, [CampaignVoter::ACCESS])
        );
    }

    // ────────────────────────────────────────────────────────
    // Admin user
    // ────────────────────────────────────────────────────────

    public function testAdminUserIsGrantedForAnyCampaign(): void
    {
        $adminUser = $this->fixtures->createRawUser('cv_admin@test.com', 'password', true);
        $token = $this->createToken($adminUser);
        $campaign = $this->fixtures->createCampaign('Admin Campaign');

        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $campaign, [CampaignVoter::OWNER])
        );
        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $campaign, [CampaignVoter::ACCESS])
        );
    }

    // ────────────────────────────────────────────────────────
    // Trusted user — ownership via campaign volunteer's user
    // ────────────────────────────────────────────────────────

    public function testTrustedUserIsOwnerWhenSharingStructureWithCampaignCreator(): void
    {
        // Creator: user + volunteer + structure
        $creatorSetup = $this->fixtures->createUserWithVolunteerAndStructure(
            'cv_creator@test.com', false, 'VOL-CREATOR', 'Shared Struct', 'STR-CV-SHARED'
        );

        // Campaign linked to creator's volunteer
        $campaign = $this->fixtures->createCampaign('Owner Campaign');
        $campaign->setVolunteer($creatorSetup['volunteer']);
        $this->em()->persist($campaign);
        $this->em()->flush();

        // Checker: different user assigned to the same structure
        $checker = $this->fixtures->createRawUser('cv_checker_owner@test.com', 'password', false);
        $this->fixtures->assignUserToStructure($checker, $creatorSetup['structure']);

        $token = $this->createToken($checker);

        $result = $this->voter->vote($token, $campaign, [CampaignVoter::OWNER]);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testTrustedUserIsNotOwnerWhenNoSharedStructureWithCreator(): void
    {
        // Creator in structure A
        $creatorSetup = $this->fixtures->createUserWithVolunteerAndStructure(
            'cv_creator2@test.com', false, 'VOL-CREATOR2', 'Creator Struct', 'STR-CV-CREATOR'
        );

        $campaign = $this->fixtures->createCampaign('Not Owner Campaign');
        $campaign->setVolunteer($creatorSetup['volunteer']);
        $this->em()->persist($campaign);
        $this->em()->flush();

        // Checker in a different structure
        $checkerSetup = $this->fixtures->createUserWithStructure(
            'cv_checker_notowner@test.com', 'Checker Struct', 'STR-CV-CHECKER'
        );

        $token = $this->createToken($checkerSetup['user']);

        // Not owner. And no campaign structures (no messages).
        $result = $this->voter->vote($token, $campaign, [CampaignVoter::OWNER]);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    // ────────────────────────────────────────────────────────
    // Trusted user — access via campaign's volunteer structures
    // ────────────────────────────────────────────────────────

    public function testTrustedUserHasAccessViaCampaignVolunteerStructures(): void
    {
        $em = $this->em();

        // Build the campaign graph manually to ensure proper persistence
        // with DEFERRED_EXPLICIT change tracking on Volunteer.
        $structure = $this->fixtures->createStructure('Campaign Struct', 'STR-CV-ACCESS');

        // Create a standalone volunteer and explicitly persist the structure relationship
        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-CV-ACCESS', 'cv_vol_access@test.com');
        $volunteer->addStructure($structure);
        $em->persist($volunteer);
        $em->flush();

        // Create campaign with communication and message linking the volunteer
        $campaign = $this->fixtures->createCampaign('Access Campaign');
        $communication = $this->fixtures->createCommunication($campaign);
        $message = $this->fixtures->createMessage($communication, $volunteer);

        // A user who is assigned to the same structure
        $accessUser = $this->fixtures->createRawUser('cv_accessor@test.com', 'password', false);
        $this->fixtures->assignUserToStructure($accessUser, $structure);

        $token = $this->createToken($accessUser);

        $result = $this->voter->vote($token, $campaign, [CampaignVoter::ACCESS]);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testTrustedUserIsDeniedWhenNoStructureOverlapWithCampaignVolunteers(): void
    {
        $em = $this->em();

        // Campaign volunteer in structure A
        $structureA = $this->fixtures->createStructure('Struct A', 'STR-CV-A');
        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-CV-NOACCESS', 'cv_vol_no@test.com');
        $volunteer->addStructure($structureA);
        $em->persist($volunteer);
        $em->flush();

        $campaign = $this->fixtures->createCampaign('No Access Campaign');
        $communication = $this->fixtures->createCommunication($campaign);
        $message = $this->fixtures->createMessage($communication, $volunteer);

        // User in a completely different structure B
        $otherSetup = $this->fixtures->createUserWithStructure(
            'cv_other_noaccess@test.com', 'Struct B', 'STR-CV-B'
        );

        $token = $this->createToken($otherSetup['user']);

        $result = $this->voter->vote($token, $campaign, [CampaignVoter::ACCESS]);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testCampaignWithNoVolunteerAndNoMessages(): void
    {
        // Campaign with no volunteer set (no creator) and no messages
        $campaign = $this->fixtures->createCampaign('Orphan Campaign');

        $userSetup = $this->fixtures->createUserWithStructure(
            'cv_orphan@test.com', 'Some Structure', 'STR-CV-ORPHAN'
        );

        $token = $this->createToken($userSetup['user']);

        // No owner (campaign has no volunteer), and no campaign structures (no messages)
        $result = $this->voter->vote($token, $campaign, [CampaignVoter::OWNER]);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testCampaignWithVolunteerButNoUserIsNotOwner(): void
    {
        // A volunteer without a user account
        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-NOUSER');

        $campaign = $this->fixtures->createCampaign('No User Campaign');
        $campaign->setVolunteer($volunteer);
        $this->em()->persist($campaign);
        $this->em()->flush();

        $userSetup = $this->fixtures->createUserWithStructure(
            'cv_nouser@test.com', 'User Structure', 'STR-CV-NOUSER'
        );

        $token = $this->createToken($userSetup['user']);

        // volunteer->getUser() returns null, so ownership check is skipped
        // Falls through to campaign structures check (no messages, so no structures)
        $result = $this->voter->vote($token, $campaign, [CampaignVoter::OWNER]);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    // ────────────────────────────────────────────────────────
    // Unauthenticated — throws exception (no token in storage)
    // ────────────────────────────────────────────────────────

    public function testUnauthenticatedUserThrowsException(): void
    {
        $campaign = $this->fixtures->createCampaign('Anon Campaign');

        static::getContainer()->get('security.token_storage')->setToken(null);
        $token = $this->createMock(UsernamePasswordToken::class);

        $this->expectException(AuthenticationCredentialsNotFoundException::class);
        $this->voter->vote($token, $campaign, [CampaignVoter::ACCESS]);
    }
}
