<?php

namespace App\Tests\Security\Voter;

use App\Entity\Badge;
use App\Entity\Volunteer;
use App\Security\Voter\VolunteerVoter;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class VolunteerVoterTest extends KernelTestCase
{
    private VolunteerVoter $voter;
    private DataFixtures $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->voter = $container->get(VolunteerVoter::class);
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

    // ────────────────────────────────────────────────────────
    // supports()
    // ────────────────────────────────────────────────────────

    public function testSupportsReturnsTrueForVolunteerSubject(): void
    {
        $rootUser = $this->fixtures->createRawUser('vv_supports@test.com', 'password', true);
        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-SUP-001');
        $token = $this->createToken($rootUser);

        $result = $this->voter->vote($token, $volunteer, ['VOLUNTEER']);
        $this->assertNotEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForNonVolunteerSubject(): void
    {
        $rootUser = $this->fixtures->createRawUser('vv_nonsub@test.com', 'password', true);
        $token = $this->createToken($rootUser);
        $badge = $this->fixtures->createBadge('Not A Volunteer', 'NOT-VOL-001');

        $result = $this->voter->vote($token, $badge, ['VOLUNTEER']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForNullSubject(): void
    {
        $rootUser = $this->fixtures->createRawUser('vv_null@test.com', 'password', true);
        $token = $this->createToken($rootUser);

        $result = $this->voter->vote($token, null, ['VOLUNTEER']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    // ────────────────────────────────────────────────────────
    // Root user
    // ────────────────────────────────────────────────────────

    public function testRootUserIsGrantedForAnyVolunteer(): void
    {
        $rootUser = $this->fixtures->createRawUser('vv_root@test.com', 'password', true);
        $rootUser->setIsRoot(true);
        static::getContainer()->get('doctrine.orm.entity_manager')->persist($rootUser);
        static::getContainer()->get('doctrine.orm.entity_manager')->flush();

        $token = $this->createToken($rootUser);
        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-ROOT-001');

        $result = $this->voter->vote($token, $volunteer, ['VOLUNTEER']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    // ────────────────────────────────────────────────────────
    // Admin user
    // ────────────────────────────────────────────────────────

    public function testAdminUserIsGrantedForAnyVolunteer(): void
    {
        $adminUser = $this->fixtures->createRawUser('vv_admin@test.com', 'password', true);
        $token = $this->createToken($adminUser);
        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-ADM-001');

        $result = $this->voter->vote($token, $volunteer, ['VOLUNTEER']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    // ────────────────────────────────────────────────────────
    // Trusted (non-admin) user — structure overlap
    // ────────────────────────────────────────────────────────

    public function testTrustedUserIsGrantedWhenSharingStructureWithVolunteer(): void
    {
        $setup = $this->fixtures->createUserWithStructure(
            'vv_shared@test.com', 'Shared Structure', 'STR-VV-SHARED'
        );
        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-SHARED-001');
        $this->fixtures->assignVolunteerToStructure($volunteer, $setup['structure']);

        $token = $this->createToken($setup['user']);

        $result = $this->voter->vote($token, $volunteer, ['VOLUNTEER']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testTrustedUserIsDeniedWhenNoSharedStructureWithVolunteer(): void
    {
        $setup = $this->fixtures->createUserWithStructure(
            'vv_noshare@test.com', 'User Structure', 'STR-VV-USER'
        );

        $otherStructure = $this->fixtures->createStructure('Volunteer Structure', 'STR-VV-VOL');
        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-NOSHARE-001');
        $this->fixtures->assignVolunteerToStructure($volunteer, $otherStructure);

        $token = $this->createToken($setup['user']);

        $result = $this->voter->vote($token, $volunteer, ['VOLUNTEER']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testTrustedUserIsGrantedWhenVolunteerHasMultipleStructuresWithOneShared(): void
    {
        $setup = $this->fixtures->createUserWithStructure(
            'vv_multi@test.com', 'Shared Struct', 'STR-VV-MULTI-S'
        );

        $otherStructure = $this->fixtures->createStructure('Other Struct', 'STR-VV-MULTI-O');
        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-MULTI-001');
        $this->fixtures->assignVolunteerToStructure($volunteer, $otherStructure);
        $this->fixtures->assignVolunteerToStructure($volunteer, $setup['structure']);

        $token = $this->createToken($setup['user']);

        $result = $this->voter->vote($token, $volunteer, ['VOLUNTEER']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testTrustedUserIsDeniedForVolunteerWithNoStructures(): void
    {
        $setup = $this->fixtures->createUserWithStructure(
            'vv_nostr@test.com', 'My Structure', 'STR-VV-NOSTR'
        );
        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-NOSTR-001');

        $token = $this->createToken($setup['user']);

        $result = $this->voter->vote($token, $volunteer, ['VOLUNTEER']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testTrustedUserWithNoStructuresIsDenied(): void
    {
        $user = $this->fixtures->createRawUser('vv_noownstr@test.com', 'password', false);
        $structure = $this->fixtures->createStructure('Vol Struct', 'STR-VV-VOLONLY');
        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-VOLONLY-001');
        $this->fixtures->assignVolunteerToStructure($volunteer, $structure);

        $token = $this->createToken($user);

        $result = $this->voter->vote($token, $volunteer, ['VOLUNTEER']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    // ────────────────────────────────────────────────────────
    // Unauthenticated
    // ────────────────────────────────────────────────────────

    public function testUnauthenticatedUserThrowsException(): void
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-ANON-001');

        static::getContainer()->get('security.token_storage')->setToken(null);
        $token = $this->createMock(UsernamePasswordToken::class);

        $this->expectException(AuthenticationCredentialsNotFoundException::class);
        $this->voter->vote($token, $volunteer, ['VOLUNTEER']);
    }
}
