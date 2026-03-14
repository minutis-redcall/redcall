<?php

namespace App\Tests\Security\Voter;

use App\Entity\Category;
use App\Entity\Structure;
use App\Security\Voter\CategoryVoter;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

class CategoryVoterTest extends KernelTestCase
{
    private CategoryVoter $voter;
    private DataFixtures $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->voter = $container->get(CategoryVoter::class);
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

    public function testSupportsReturnsTrueForCategorySubject(): void
    {
        $category = $this->fixtures->createCategory('Supports Cat', 'CAT-SUP-001');

        $rootUser = $this->fixtures->createRawUser('cat_supports@test.com', 'password', true);
        $token = $this->createToken($rootUser);

        $result = $this->voter->vote($token, $category, ['CATEGORY']);
        $this->assertNotEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForNonCategorySubject(): void
    {
        $rootUser = $this->fixtures->createRawUser('cat_nonsub@test.com', 'password', true);
        $token = $this->createToken($rootUser);

        $structure = $this->fixtures->createStructure('Not A Category', 'NOT-CAT-001');

        $result = $this->voter->vote($token, $structure, ['CATEGORY']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testSupportsReturnsFalseForNullSubject(): void
    {
        $rootUser = $this->fixtures->createRawUser('cat_null@test.com', 'password', true);
        $token = $this->createToken($rootUser);

        $result = $this->voter->vote($token, null, ['CATEGORY']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testRootUserIsGranted(): void
    {
        $rootUser = $this->fixtures->createRawUser('cat_root@test.com', 'password', true);
        $rootUser->setIsRoot(true);
        static::getContainer()->get('doctrine.orm.entity_manager')->persist($rootUser);
        static::getContainer()->get('doctrine.orm.entity_manager')->flush();

        $token = $this->createToken($rootUser);
        $category = $this->fixtures->createCategory('Root Cat', 'CAT-ROOT-001');

        $result = $this->voter->vote($token, $category, ['CATEGORY']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAdminUserIsGranted(): void
    {
        $adminUser = $this->fixtures->createRawUser('cat_admin@test.com', 'password', true);
        $token = $this->createToken($adminUser);
        $category = $this->fixtures->createCategory('Admin Cat', 'CAT-ADM-001');

        $result = $this->voter->vote($token, $category, ['CATEGORY']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testTrustedNonAdminUserIsDenied(): void
    {
        $user = $this->fixtures->createRawUser('cat_trusted@test.com', 'password', false);
        $token = $this->createToken($user);
        $category = $this->fixtures->createCategory('Trusted Cat', 'CAT-TRU-001');

        $result = $this->voter->vote($token, $category, ['CATEGORY']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUnauthenticatedUserThrowsException(): void
    {
        $category = $this->fixtures->createCategory('Anon Cat', 'CAT-ANON-001');

        static::getContainer()->get('security.token_storage')->setToken(null);
        $token = $this->createMock(UsernamePasswordToken::class);

        $this->expectException(AuthenticationCredentialsNotFoundException::class);
        $this->voter->vote($token, $category, ['CATEGORY']);
    }

    public function testAnyAttributeIsAcceptedWhenSubjectIsCategory(): void
    {
        $adminUser = $this->fixtures->createRawUser('cat_anyattr@test.com', 'password', true);
        $token = $this->createToken($adminUser);
        $category = $this->fixtures->createCategory('Any Attr Cat', 'CAT-ANY-001');

        $result = $this->voter->vote($token, $category, ['RANDOM_ATTR']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }
}
