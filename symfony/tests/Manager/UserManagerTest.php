<?php

namespace App\Tests\Manager;

use App\Entity\User;
use App\Entity\UserAuditLog;
use App\Manager\UserManager;
use App\Tests\Fixtures\DataFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UserManagerTest extends KernelTestCase
{
    private UserManager $manager;
    private DataFixtures $fixtures;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->manager = $container->get(UserManager::class);
        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->fixtures = new DataFixtures(
            $this->em,
            $container->get('security.password_hasher')
        );
    }

    private function loginAs($user): void
    {
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        static::getContainer()->get('security.token_storage')->setToken($token);
    }

    // ──────────────────────────────────────────────
    // changeLocale
    // ──────────────────────────────────────────────

    public function testChangeLocaleSetsLocaleOnUser(): void
    {
        $user = $this->fixtures->createRawUser('um_locale1@test.com');
        $this->assertSame('fr', $user->getLocale());

        $this->manager->changeLocale($user, 'en');

        $this->em->clear();
        $reloaded = $this->em->getRepository(User::class)->find($user->getId());

        $this->assertSame('en', $reloaded->getLocale());
    }

    public function testChangeLocaleMultipleTimes(): void
    {
        $user = $this->fixtures->createRawUser('um_locale2@test.com');

        $this->manager->changeLocale($user, 'en');
        $this->manager->changeLocale($user, 'fr');

        $this->em->clear();
        $reloaded = $this->em->getRepository(User::class)->find($user->getId());

        $this->assertSame('fr', $reloaded->getLocale());
    }

    // ──────────────────────────────────────────────
    // changeExternalId
    // ──────────────────────────────────────────────

    public function testChangeExternalIdAssignsNivolToUser(): void
    {
        $user = $this->fixtures->createRawUser('um_chgvol1@test.com');
        $volunteer = $this->fixtures->createStandaloneVolunteer('UM-VOL-001', 'um_vol1@test.com');

        $this->manager->changeExternalId($user, $volunteer->getExternalId());

        $this->em->clear();
        $reloaded = $this->em->getRepository(User::class)->find($user->getId());

        $this->assertSame('UM-VOL-001', $reloaded->getExternalId());
    }

    public function testChangeExternalIdClearsWhenNullPassed(): void
    {
        $user = $this->fixtures->createRawUser('um_chgvol3@test.com');
        $user->setExternalId('UM-VOL-003');
        $this->em->persist($user);
        $this->em->flush();

        $this->manager->changeExternalId($user, null);

        $this->em->clear();
        $reloaded = $this->em->getRepository(User::class)->find($user->getId());

        $this->assertNull($reloaded->getExternalId());
    }

    public function testChangeExternalIdDoesNothingWhenUserIsLocked(): void
    {
        $user = $this->fixtures->createRawUser('um_chgvol4@test.com');
        $user->setExternalId('UM-VOL-004');
        $user->setLocked(true);
        $this->em->persist($user);
        $this->em->flush();

        $this->manager->changeExternalId($user, 'UM-VOL-005');

        // External id is unchanged because the user is locked.
        $this->em->clear();
        $reloaded = $this->em->getRepository(User::class)->find($user->getId());
        $this->assertSame('UM-VOL-004', $reloaded->getExternalId());
    }

    // ──────────────────────────────────────────────
    // getRedCallUsersInStructure
    // ──────────────────────────────────────────────

    public function testGetRedCallUsersInStructureReturnsTrustedUsers(): void
    {
        $structure = $this->fixtures->createStructure('UM STRUCT RC', 'UM-EXT-003');
        $user = $this->fixtures->createRawUser('um_rc1@test.com');
        $this->fixtures->assignUserToStructure($user, $structure);

        $users = $this->manager->getRedCallUsersInStructure($structure, false);

        $ids = array_map(fn(User $u) => $u->getId(), $users);
        $this->assertContains($user->getId(), $ids);
    }

    public function testGetRedCallUsersInStructureExcludesNonTrusted(): void
    {
        $structure = $this->fixtures->createStructure('UM STRUCT RC2', 'UM-EXT-004');
        $user = $this->fixtures->createRawUser('um_rc2@test.com');
        $user->setIsTrusted(false);
        $this->em->persist($user);
        $this->em->flush();
        $this->fixtures->assignUserToStructure($user, $structure);

        $users = $this->manager->getRedCallUsersInStructure($structure, false);

        $ids = array_map(fn(User $u) => $u->getId(), $users);
        $this->assertNotContains($user->getId(), $ids);
    }

    public function testGetRedCallUsersInStructureIncludesChildrenWhenRequested(): void
    {
        $parentStructure = $this->fixtures->createStructure('UM PARENT', 'UM-EXT-005');
        $childStructure = $this->fixtures->createStructure('UM CHILD', 'UM-EXT-006');
        $childStructure->setParentStructure($parentStructure);
        $parentStructure->addChildrenStructure($childStructure);
        $this->em->persist($childStructure);
        $this->em->persist($parentStructure);
        $this->em->flush();

        $parentUser = $this->fixtures->createRawUser('um_parent_u@test.com');
        $childUser = $this->fixtures->createRawUser('um_child_u@test.com');
        $this->fixtures->assignUserToStructure($parentUser, $parentStructure);
        $this->fixtures->assignUserToStructure($childUser, $childStructure);

        $users = $this->manager->getRedCallUsersInStructure($parentStructure, true);

        $ids = array_map(fn(User $u) => $u->getId(), $users);
        $this->assertContains($parentUser->getId(), $ids);
        $this->assertContains($childUser->getId(), $ids);
    }

    public function testGetRedCallUsersInStructureWithoutChildrenDoesNotIncludeThem(): void
    {
        $parentStructure = $this->fixtures->createStructure('UM PARENT2', 'UM-EXT-007');
        $childStructure = $this->fixtures->createStructure('UM CHILD2', 'UM-EXT-008');
        $childStructure->setParentStructure($parentStructure);
        $parentStructure->addChildrenStructure($childStructure);
        $this->em->persist($childStructure);
        $this->em->persist($parentStructure);
        $this->em->flush();

        $childUser = $this->fixtures->createRawUser('um_child_u2@test.com');
        $this->fixtures->assignUserToStructure($childUser, $childStructure);

        $users = $this->manager->getRedCallUsersInStructure($parentStructure, false);

        $ids = array_map(fn(User $u) => $u->getId(), $users);
        $this->assertNotContains($childUser->getId(), $ids);
    }

    // ──────────────────────────────────────────────
    // createUser
    // ──────────────────────────────────────────────

    public function testCreateUserCreatesUserAndAttributesActor(): void
    {
        $actor     = $this->fixtures->createRawUser('um_create_actor@test.com');
        $volunteer = $this->fixtures->createStandaloneVolunteer('UM-VOL-CREATE-001', 'um_create@test.com');

        $user = $this->manager->createUser($volunteer->getExternalId(), $actor);

        $this->assertSame('um_create@test.com', $user->getUsername());

        $logs = $this->em->getRepository(UserAuditLog::class)->findBy(['targetUser' => $user]);
        $this->assertNotEmpty($logs);
        $this->assertNotNull($logs[0]->getActor());
        $this->assertSame($actor->getId(), $logs[0]->getActor()->getId());
    }

    public function testCreateUserRefusesUnknownExternalId(): void
    {
        $this->expectException(\LogicException::class);

        $this->manager->createUser('UM-VOL-DOES-NOT-EXIST');
    }
}
