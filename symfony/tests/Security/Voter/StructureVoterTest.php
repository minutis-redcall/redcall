<?php

namespace App\Tests\Security\Voter;

use App\Entity\Badge;
use App\Entity\Structure;
use App\Security\Voter\StructureVoter;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class StructureVoterTest extends KernelTestCase
{
    private StructureVoter $voter;
    private DataFixtures $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->voter = $container->get(StructureVoter::class);
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

    public function testSupportsReturnsTrueForStructureSubject(): void
    {
        $structure = $this->fixtures->createStructure('Supports Struct', 'STR-SUP-001');
        $rootUser = $this->fixtures->createRawUser('sv_supports@test.com', 'password', true);
        $token = $this->createToken($rootUser);

        $result = $this->voter->vote($token, $structure, ['STRUCTURE']);
        $this->assertNotEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForNonStructureSubject(): void
    {
        $rootUser = $this->fixtures->createRawUser('sv_nonsub@test.com', 'password', true);
        $token = $this->createToken($rootUser);
        $badge = $this->fixtures->createBadge('Not A Structure', 'NOT-STR-001');

        $result = $this->voter->vote($token, $badge, ['STRUCTURE']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForNullSubject(): void
    {
        $rootUser = $this->fixtures->createRawUser('sv_null@test.com', 'password', true);
        $token = $this->createToken($rootUser);

        $result = $this->voter->vote($token, null, ['STRUCTURE']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    // ────────────────────────────────────────────────────────
    // Root user
    // ────────────────────────────────────────────────────────

    public function testRootUserIsGrantedForAnyStructure(): void
    {
        $rootUser = $this->fixtures->createRawUser('sv_root@test.com', 'password', true);
        $rootUser->setIsRoot(true);
        static::getContainer()->get('doctrine.orm.entity_manager')->persist($rootUser);
        static::getContainer()->get('doctrine.orm.entity_manager')->flush();

        $token = $this->createToken($rootUser);

        // Root can access a structure they are NOT assigned to
        $structure = $this->fixtures->createStructure('Unrelated Struct', 'STR-ROOT-001');

        $result = $this->voter->vote($token, $structure, ['STRUCTURE']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    // ────────────────────────────────────────────────────────
    // Admin user
    // ────────────────────────────────────────────────────────

    public function testAdminUserIsGrantedForAnyStructure(): void
    {
        $adminUser = $this->fixtures->createRawUser('sv_admin@test.com', 'password', true);
        $token = $this->createToken($adminUser);

        // Admin can access a structure they are NOT assigned to
        $structure = $this->fixtures->createStructure('Admin Struct', 'STR-ADM-001');

        $result = $this->voter->vote($token, $structure, ['STRUCTURE']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    // ────────────────────────────────────────────────────────
    // Trusted (non-admin) user — structure membership
    // ────────────────────────────────────────────────────────

    public function testTrustedUserIsGrantedForOwnStructure(): void
    {
        $setup = $this->fixtures->createUserWithStructure(
            'sv_own@test.com', 'Own Structure', 'STR-OWN-001'
        );
        $token = $this->createToken($setup['user']);

        $result = $this->voter->vote($token, $setup['structure'], ['STRUCTURE']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testTrustedUserIsDeniedForUnrelatedStructure(): void
    {
        $setup = $this->fixtures->createUserWithStructure(
            'sv_unrelated@test.com', 'My Structure', 'STR-MY-001'
        );
        $token = $this->createToken($setup['user']);

        $otherStructure = $this->fixtures->createStructure('Other Structure', 'STR-OTHER-001');

        $result = $this->voter->vote($token, $otherStructure, ['STRUCTURE']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testTrustedUserWithMultipleStructuresIsGrantedForEach(): void
    {
        $user = $this->fixtures->createRawUser('sv_multi@test.com', 'password', false);
        $structureA = $this->fixtures->createStructure('Structure A', 'STR-MULTI-A');
        $structureB = $this->fixtures->createStructure('Structure B', 'STR-MULTI-B');

        $this->fixtures->assignUserToStructure($user, $structureA);
        $this->fixtures->assignUserToStructure($user, $structureB);

        $token = $this->createToken($user);

        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $structureA, ['STRUCTURE'])
        );
        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $structureB, ['STRUCTURE'])
        );
    }

    public function testTrustedUserIsDeniedForDisabledOwnStructure(): void
    {
        $user = $this->fixtures->createRawUser('sv_disabled@test.com', 'password', false);
        $disabledStructure = $this->fixtures->createStructure('Disabled', 'STR-DIS-001', false);
        $this->fixtures->assignUserToStructure($user, $disabledStructure);

        $token = $this->createToken($user);

        // User.getStructures(true) filters to enabled only, so disabled structure is not in collection
        $result = $this->voter->vote($token, $disabledStructure, ['STRUCTURE']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    // ────────────────────────────────────────────────────────
    // Unauthenticated
    // ────────────────────────────────────────────────────────

    public function testUnauthenticatedUserThrowsException(): void
    {
        $structure = $this->fixtures->createStructure('Anon Struct', 'STR-ANON-001');

        static::getContainer()->get('security.token_storage')->setToken(null);
        $token = $this->createMock(UsernamePasswordToken::class);

        $this->expectException(AuthenticationCredentialsNotFoundException::class);
        $this->voter->vote($token, $structure, ['STRUCTURE']);
    }
}
