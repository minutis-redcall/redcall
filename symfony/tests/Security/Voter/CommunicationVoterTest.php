<?php

namespace App\Tests\Security\Voter;

use App\Entity\Badge;
use App\Entity\Communication;
use App\Security\Voter\CommunicationVoter;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class CommunicationVoterTest extends KernelTestCase
{
    private CommunicationVoter $voter;
    private DataFixtures $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->voter = $container->get(CommunicationVoter::class);
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

    public function testSupportsReturnsTrueForCommunicationSubject(): void
    {
        $campaign = $this->fixtures->createCampaign('Comm Supports');
        $communication = $this->fixtures->createCommunication($campaign);

        $rootUser = $this->fixtures->createRawUser('comv_supports@test.com', 'password', true);
        $token = $this->createToken($rootUser);

        $result = $this->voter->vote($token, $communication, ['COMMUNICATION']);
        $this->assertNotEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForNonCommunicationSubject(): void
    {
        $rootUser = $this->fixtures->createRawUser('comv_nonsub@test.com', 'password', true);
        $token = $this->createToken($rootUser);
        $badge = $this->fixtures->createBadge('Not A Comm', 'NOT-COMM-001');

        $result = $this->voter->vote($token, $badge, ['COMMUNICATION']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForNullSubject(): void
    {
        $rootUser = $this->fixtures->createRawUser('comv_null@test.com', 'password', true);
        $token = $this->createToken($rootUser);

        $result = $this->voter->vote($token, null, ['COMMUNICATION']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    // ────────────────────────────────────────────────────────
    // Admin user — always granted
    // ────────────────────────────────────────────────────────

    public function testAdminUserIsGranted(): void
    {
        $adminUser = $this->fixtures->createRawUser('comv_admin@test.com', 'password', true);
        $token = $this->createToken($adminUser);

        $campaign = $this->fixtures->createCampaign('Admin Comm Campaign');
        $communication = $this->fixtures->createCommunication($campaign);

        $result = $this->voter->vote($token, $communication, ['COMMUNICATION']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testRootUserIsGranted(): void
    {
        $rootUser = $this->fixtures->createRawUser('comv_root@test.com', 'password', true);
        $rootUser->setIsRoot(true);
        $this->em()->persist($rootUser);
        $this->em()->flush();

        $token = $this->createToken($rootUser);

        $campaign = $this->fixtures->createCampaign('Root Comm Campaign');
        $communication = $this->fixtures->createCommunication($campaign);

        $result = $this->voter->vote($token, $communication, ['COMMUNICATION']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    // ────────────────────────────────────────────────────────
    // Trusted user — delegates to CampaignVoter via CAMPAIGN_ACCESS
    // ────────────────────────────────────────────────────────

    public function testTrustedUserIsGrantedWhenHasCampaignAccess(): void
    {
        $em = $this->em();

        // Build campaign graph with explicit volunteer persistence (DEFERRED_EXPLICIT)
        $structure = $this->fixtures->createStructure('Comm Access Struct', 'STR-COMV-ACCESS');

        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-COMV-ACC', 'comv_vol_acc@test.com');
        $volunteer->addStructure($structure);
        $em->persist($volunteer);
        $em->flush();

        $campaign = $this->fixtures->createCampaign('Access Comm Campaign');
        $communication = $this->fixtures->createCommunication($campaign);
        $message = $this->fixtures->createMessage($communication, $volunteer);

        // User assigned to the same structure
        $accessUser = $this->fixtures->createRawUser('comv_accessor@test.com', 'password', false);
        $this->fixtures->assignUserToStructure($accessUser, $structure);

        $token = $this->createToken($accessUser);

        $result = $this->voter->vote($token, $communication, ['COMMUNICATION']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testTrustedUserIsDeniedWhenNoCampaignAccess(): void
    {
        $em = $this->em();

        // Campaign volunteer in structure A
        $structureA = $this->fixtures->createStructure('Struct A', 'STR-COMV-A');
        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-COMV-NOAC', 'comv_vol_no@test.com');
        $volunteer->addStructure($structureA);
        $em->persist($volunteer);
        $em->flush();

        $campaign = $this->fixtures->createCampaign('No Access Comm Campaign');
        $communication = $this->fixtures->createCommunication($campaign);
        $message = $this->fixtures->createMessage($communication, $volunteer);

        // Different user in structure B
        $otherSetup = $this->fixtures->createUserWithStructure(
            'comv_other@test.com', 'Struct B', 'STR-COMV-B'
        );

        $token = $this->createToken($otherSetup['user']);

        $result = $this->voter->vote($token, $communication, ['COMMUNICATION']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testTrustedUserIsGrantedViaCampaignOwnership(): void
    {
        // Creator: user + volunteer + structure
        $creatorSetup = $this->fixtures->createUserWithVolunteerAndStructure(
            'comv_creator@test.com', false, 'VOL-COMV-CR', 'Creator Struct', 'STR-COMV-CR'
        );

        // Campaign linked to creator's volunteer
        $campaign = $this->fixtures->createCampaign('Owner Comm Campaign');
        $campaign->setVolunteer($creatorSetup['volunteer']);
        $this->em()->persist($campaign);
        $this->em()->flush();

        $communication = $this->fixtures->createCommunication($campaign);

        // Checker shares structure with the creator
        $checker = $this->fixtures->createRawUser('comv_checker@test.com', 'password', false);
        $this->fixtures->assignUserToStructure($checker, $creatorSetup['structure']);

        $token = $this->createToken($checker);

        $result = $this->voter->vote($token, $communication, ['COMMUNICATION']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    // ────────────────────────────────────────────────────────
    // Unauthenticated — throws exception (no token in storage)
    // ────────────────────────────────────────────────────────

    public function testUnauthenticatedUserThrowsException(): void
    {
        $campaign = $this->fixtures->createCampaign('Anon Comm Campaign');
        $communication = $this->fixtures->createCommunication($campaign);

        static::getContainer()->get('security.token_storage')->setToken(null);
        $token = $this->createMock(UsernamePasswordToken::class);

        $this->expectException(AuthenticationCredentialsNotFoundException::class);
        $this->voter->vote($token, $communication, ['COMMUNICATION']);
    }

    // ────────────────────────────────────────────────────────
    // Any attribute is accepted (supports only checks subject type)
    // ────────────────────────────────────────────────────────

    public function testAnyAttributeIsAcceptedWhenSubjectIsCommunication(): void
    {
        $adminUser = $this->fixtures->createRawUser('comv_anyattr@test.com', 'password', true);
        $token = $this->createToken($adminUser);

        $campaign = $this->fixtures->createCampaign('Any Attr Comm');
        $communication = $this->fixtures->createCommunication($campaign);

        $result = $this->voter->vote($token, $communication, ['WHATEVER']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }
}
