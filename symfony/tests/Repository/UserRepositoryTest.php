<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    /** @var UserRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(User::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    // ── findOneByUsername ──

    public function testFindOneByUsername(): void
    {
        $user = $this->fixtures->createRawUser('findme@test.com');

        $found = $this->repository->findOneByUsername('findme@test.com');
        $this->assertNotNull($found);
        $this->assertSame($user->getId(), $found->getId());
    }

    public function testFindOneByUsernameReturnsNull(): void
    {
        $this->assertNull($this->repository->findOneByUsername('nonexistent@test.com'));
    }

    // ── findOneByExternalId ──

    public function testFindOneByExternalId(): void
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'extuser@test.com', false, 'UEXT-001', 'UExt Structure', 'UEXT-EXT'
        );

        $found = $this->repository->findOneByExternalId('UEXT-001');
        $this->assertNotNull($found);
        $this->assertSame($setup['user']->getId(), $found->getId());
    }

    public function testFindOneByExternalIdReturnsNull(): void
    {
        $this->assertNull($this->repository->findOneByExternalId('NONEXISTENT-VOL'));
    }

    // ── searchQueryBuilder ──

    public function testSearchQueryBuilder(): void
    {
        $this->fixtures->createRawUser('searchable_user@test.com');

        $results = $this->repository->searchQueryBuilder('searchable_user', null)
            ->getQuery()->getResult();

        $usernames = array_map(function (User $u) { return $u->getUsername(); }, $results);
        $this->assertContains('searchable_user@test.com', $usernames);
    }

    public function testSearchQueryBuilderOnlyAdmins(): void
    {
        $this->fixtures->createRawUser('admin_search@test.com', 'password', true);
        $this->fixtures->createRawUser('nonadmin_search@test.com', 'password', false);

        $results = $this->repository->searchQueryBuilder('search@test.com', true)
            ->getQuery()->getResult();

        $usernames = array_map(function (User $u) { return $u->getUsername(); }, $results);
        $this->assertContains('admin_search@test.com', $usernames);
        $this->assertNotContains('nonadmin_search@test.com', $usernames);
    }

    // ── getRedCallUsersInStructure ──

    public function testGetRedCallUsersInStructure(): void
    {
        $setup = $this->fixtures->createUserWithStructure(
            'redcall@test.com', 'RedCall Structure', 'RC-EXT-001'
        );

        $results = $this->repository->getRedCallUsersInStructure($setup['structure']);

        $ids = array_map(function (User $u) { return $u->getId(); }, $results);
        $this->assertContains($setup['user']->getId(), $ids);
    }

    // ── createTrustedUserQueryBuilder ──

    public function testCreateTrustedUserQueryBuilder(): void
    {
        $this->fixtures->createRawUser('trusted@test.com');

        $results = $this->repository->createTrustedUserQueryBuilder()
            ->getQuery()->getResult();

        $usernames = array_map(function (User $u) { return $u->getUsername(); }, $results);
        $this->assertContains('trusted@test.com', $usernames);
    }

    // ── findAllWithStructure ──

    public function testFindAllWithStructure(): void
    {
        $setup = $this->fixtures->createUserWithStructure(
            'withstruct@test.com', 'WithStruct Structure', 'WS-EXT-001'
        );

        $results = $this->repository->findAllWithStructure($setup['structure']);

        $ids = array_map(function (User $u) { return $u->getId(); }, $results);
        $this->assertContains($setup['user']->getId(), $ids);
    }

    // ── save / remove ──

    public function testSaveAndRemove(): void
    {
        $user = new User();
        $user->setUsername('saveremove@test.com');
        $user->setLocale('fr');
        $user->setTimezone('Europe/Paris');
        $encoder = self::$container->get('security.password_encoder');
        $user->setPassword($encoder->encodePassword($user, 'password'));
        $user->setIsVerified(true);
        $user->setIsTrusted(true);

        $this->repository->save($user);

        $found = $this->repository->findOneByUsername('saveremove@test.com');
        $this->assertNotNull($found);

        $this->repository->remove($found);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $this->assertNull($this->repository->findOneByUsername('saveremove@test.com'));
    }

    // ── findAll ──

    public function testFindAll(): void
    {
        $this->fixtures->createRawUser('findall@test.com');

        $all = $this->repository->findAll();
        $this->assertNotEmpty($all);
    }
}
