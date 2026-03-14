<?php

namespace App\Tests\Security\Voter;

use App\Entity\Structure;
use App\Entity\User;
use App\Security\Voter\UserVoter;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class UserVoterTest extends KernelTestCase
{
    private UserVoter $voter;
    private DataFixtures $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->voter = $container->get(UserVoter::class);
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

    public function testSupportsReturnsTrueForUserSubject(): void
    {
        $rootUser = $this->fixtures->createRawUser('uv_supports_root@test.com', 'password', true);
        $subjectUser = $this->fixtures->createRawUser('uv_supports_sub@test.com', 'password', false);
        $token = $this->createToken($rootUser);

        $result = $this->voter->vote($token, $subjectUser, ['USER']);
        $this->assertNotEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForNonUserSubject(): void
    {
        $rootUser = $this->fixtures->createRawUser('uv_nonsub@test.com', 'password', true);
        $token = $this->createToken($rootUser);

        $structure = $this->fixtures->createStructure('Not A User', 'NOT-USER-001');

        $result = $this->voter->vote($token, $structure, ['USER']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForNullSubject(): void
    {
        $rootUser = $this->fixtures->createRawUser('uv_null@test.com', 'password', true);
        $token = $this->createToken($rootUser);

        $result = $this->voter->vote($token, null, ['USER']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testRootUserIsGranted(): void
    {
        $rootUser = $this->fixtures->createRawUser('uv_root@test.com', 'password', true);
        $rootUser->setIsRoot(true);
        static::getContainer()->get('doctrine.orm.entity_manager')->persist($rootUser);
        static::getContainer()->get('doctrine.orm.entity_manager')->flush();

        $subjectUser = $this->fixtures->createRawUser('uv_root_sub@test.com', 'password', false);
        $token = $this->createToken($rootUser);

        $result = $this->voter->vote($token, $subjectUser, ['USER']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAdminUserIsGranted(): void
    {
        $adminUser = $this->fixtures->createRawUser('uv_admin@test.com', 'password', true);
        $subjectUser = $this->fixtures->createRawUser('uv_admin_sub@test.com', 'password', false);
        $token = $this->createToken($adminUser);

        $result = $this->voter->vote($token, $subjectUser, ['USER']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAdminUserCanVoteOnSelf(): void
    {
        $adminUser = $this->fixtures->createRawUser('uv_admin_self@test.com', 'password', true);
        $token = $this->createToken($adminUser);

        // Admin voting on themselves (the commented-out self-check means this is granted)
        $result = $this->voter->vote($token, $adminUser, ['USER']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testTrustedNonAdminUserIsDenied(): void
    {
        $user = $this->fixtures->createRawUser('uv_trusted@test.com', 'password', false);
        $subjectUser = $this->fixtures->createRawUser('uv_trusted_sub@test.com', 'password', false);
        $token = $this->createToken($user);

        $result = $this->voter->vote($token, $subjectUser, ['USER']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testTrustedUserVotingOnSelfIsDenied(): void
    {
        $user = $this->fixtures->createRawUser('uv_self@test.com', 'password', false);
        $token = $this->createToken($user);

        // Even voting on self is denied for non-admin
        $result = $this->voter->vote($token, $user, ['USER']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUnauthenticatedUserThrowsException(): void
    {
        $subjectUser = $this->fixtures->createRawUser('uv_anon_sub@test.com', 'password', false);

        static::getContainer()->get('security.token_storage')->setToken(null);
        $token = $this->createMock(UsernamePasswordToken::class);

        $this->expectException(AuthenticationCredentialsNotFoundException::class);
        $this->voter->vote($token, $subjectUser, ['USER']);
    }

    public function testAnyAttributeIsAcceptedWhenSubjectIsUser(): void
    {
        $adminUser = $this->fixtures->createRawUser('uv_anyattr@test.com', 'password', true);
        $subjectUser = $this->fixtures->createRawUser('uv_anyattr_sub@test.com', 'password', false);
        $token = $this->createToken($adminUser);

        $result = $this->voter->vote($token, $subjectUser, ['WHATEVER']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }
}
