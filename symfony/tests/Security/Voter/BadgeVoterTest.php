<?php

namespace App\Tests\Security\Voter;

use App\Entity\Badge;
use App\Entity\Structure;
use App\Security\Voter\BadgeVoter;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class BadgeVoterTest extends KernelTestCase
{
    private BadgeVoter $voter;
    private DataFixtures $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->voter = $container->get(BadgeVoter::class);
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

    private function createAnonymousToken(): UsernamePasswordToken
    {
        // Use a token with no user set in token storage
        $token = $this->createMock(UsernamePasswordToken::class);
        static::getContainer()->get('security.token_storage')->setToken(null);

        return $token;
    }

    public function testSupportsReturnsTrueForBadgeSubject(): void
    {
        $badge = $this->fixtures->createBadge('Test Badge', 'BADGE-SUP-001');

        $rootUser = $this->fixtures->createRawUser('badge_supports_root@test.com', 'password', true);
        $token = $this->createToken($rootUser);

        // If supports returns true, vote will call voteOnAttribute and return ACCESS_GRANTED/DENIED
        // If supports returns false, vote returns ACCESS_ABSTAIN
        $result = $this->voter->vote($token, $badge, ['BADGE']);
        $this->assertNotEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForNonBadgeSubject(): void
    {
        $rootUser = $this->fixtures->createRawUser('badge_nonsub@test.com', 'password', true);
        $token = $this->createToken($rootUser);

        $structure = $this->fixtures->createStructure('Not A Badge', 'NOT-BADGE-001');

        $result = $this->voter->vote($token, $structure, ['BADGE']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForNullSubject(): void
    {
        $rootUser = $this->fixtures->createRawUser('badge_null@test.com', 'password', true);
        $token = $this->createToken($rootUser);

        $result = $this->voter->vote($token, null, ['BADGE']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testRootUserIsGranted(): void
    {
        $rootUser = $this->fixtures->createRawUser('badge_root@test.com', 'password', true);
        $rootUser->setIsRoot(true);
        static::getContainer()->get('doctrine.orm.entity_manager')->persist($rootUser);
        static::getContainer()->get('doctrine.orm.entity_manager')->flush();

        $token = $this->createToken($rootUser);
        $badge = $this->fixtures->createBadge('Root Badge', 'BADGE-ROOT-001');

        $result = $this->voter->vote($token, $badge, ['BADGE']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAdminUserIsGranted(): void
    {
        $adminUser = $this->fixtures->createRawUser('badge_admin@test.com', 'password', true);
        $token = $this->createToken($adminUser);
        $badge = $this->fixtures->createBadge('Admin Badge', 'BADGE-ADM-001');

        $result = $this->voter->vote($token, $badge, ['BADGE']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testTrustedNonAdminUserIsDenied(): void
    {
        $user = $this->fixtures->createRawUser('badge_trusted@test.com', 'password', false);
        $token = $this->createToken($user);
        $badge = $this->fixtures->createBadge('Trusted Badge', 'BADGE-TRU-001');

        $result = $this->voter->vote($token, $badge, ['BADGE']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUnauthenticatedUserThrowsException(): void
    {
        $badge = $this->fixtures->createBadge('Anon Badge', 'BADGE-ANON-001');

        // Voter calls security->isGranted() which throws when token storage is empty
        static::getContainer()->get('security.token_storage')->setToken(null);
        $token = $this->createMock(UsernamePasswordToken::class);

        $this->expectException(AuthenticationCredentialsNotFoundException::class);
        $this->voter->vote($token, $badge, ['BADGE']);
    }

    public function testAnyAttributeIsAcceptedWhenSubjectIsBadge(): void
    {
        $adminUser = $this->fixtures->createRawUser('badge_anyattr@test.com', 'password', true);
        $token = $this->createToken($adminUser);
        $badge = $this->fixtures->createBadge('Any Attr Badge', 'BADGE-ANY-001');

        // The BadgeVoter supports any attribute as long as subject is a Badge
        $result = $this->voter->vote($token, $badge, ['SOME_RANDOM_ATTRIBUTE']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }
}
