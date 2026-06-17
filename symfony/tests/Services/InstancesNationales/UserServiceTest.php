<?php

namespace App\Tests\Services\InstancesNationales;

use App\Entity\Structure;
use App\Entity\User;
use App\Model\InstancesNationales\UserExtract;
use App\Model\InstancesNationales\UsersExtract;
use App\Services\InstancesNationales\UserService;
use App\Tests\Fixtures\DataFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Guards the dual-identity case: a person who is BOTH a Pegass operator (real
 * NIVOL on their User) AND listed in the Annuaire National roster under the
 * same email. The Annuaire sync must never strip such a user's NIVOL nor its
 * non-Annuaire (Pegass) structures, and must never delete it — historically a
 * source of permission loss.
 */
class UserServiceTest extends KernelTestCase
{
    private UserService $service;
    private DataFixtures $fixtures;
    private EntityManagerInterface $em;

    protected function setUp() : void
    {
        self::bootKernel();
        $container       = static::getContainer();
        $this->service   = $container->get(UserService::class);
        $this->em        = $container->get('doctrine.orm.entity_manager');
        $this->fixtures  = new DataFixtures($this->em, $container->get('security.password_hasher'));
    }

    private function extractWith(array $emails) : UsersExtract
    {
        $extract = new UsersExtract();
        foreach ($emails as $email) {
            $u = new UserExtract();
            $u->setEmail($email);
            $extract->addUser($u);
        }

        return $extract;
    }

    private function call(string $method, Structure $structure, UsersExtract $extract) : void
    {
        $ref = new \ReflectionMethod(UserService::class, $method);
        $ref->setAccessible(true);
        $ref->invoke($this->service, $structure, $extract);
    }

    public function testDeleteMissingUsersNeverStripsNivolUser() : void
    {
        $annuaire = $this->fixtures->createStructure('ANNUAIRE NATIONAL', 'ANNU-1');
        $pegassStruct = $this->fixtures->createStructure('Local Unit', 'UL-1');

        // Pegass operator: real NIVOL, a Pegass structure, AND added to the
        // Annuaire roster under the same email (the dual-identity case).
        $user = $this->fixtures->createRawUser('shared@example.com');
        $user->setExternalId('00000123A');
        $this->fixtures->assignUserToStructure($user, $pegassStruct);
        $this->fixtures->assignUserToStructure($user, $annuaire);
        $userId = $user->getId();

        // Roster no longer contains this email → would "remove" the user.
        $this->call('deleteMissingUsers', $annuaire, $this->extractWith([]));

        $this->em->clear();
        $reloaded = $this->em->getRepository(User::class)->find($userId);

        $structureIds = array_map(static function (Structure $s) {
            return $s->getExternalId();
        }, $reloaded->getStructures(false)->toArray());

        $this->assertNotNull($reloaded, 'directory (NIVOL) user must NOT be deleted by the Annuaire sync');
        $this->assertSame('00000123A', $reloaded->getExternalId(), 'NIVOL must be preserved');
        $this->assertContains('UL-1', $structureIds, 'non-Annuaire (Pegass) structure must be preserved');
        $this->assertNotContains('ANNU-1', $structureIds, 'only the Annuaire structure is removed');
    }

    public function testDeleteMissingUsersDeletesPureAnnuaireAccount() : void
    {
        $annuaire = $this->fixtures->createStructure('ANNUAIRE NATIONAL', 'ANNU-2');

        // Pure Annuaire account: NULL external_id, only the Annuaire structure.
        $user = $this->fixtures->createRawUser('annu-only@example.com');
        $this->fixtures->assignUserToStructure($user, $annuaire);
        $userId = $user->getId();

        $this->call('deleteMissingUsers', $annuaire, $this->extractWith([]));

        $this->em->clear();
        $this->assertNull(
            $this->em->getRepository(User::class)->find($userId),
            'a pure email-keyed Annuaire account with no other structures is removed'
        );
    }

    public function testCreateUsersOnlyAddsStructureToExistingNivolUser() : void
    {
        $annuaire     = $this->fixtures->createStructure('ANNUAIRE NATIONAL', 'ANNU-3');
        $pegassStruct = $this->fixtures->createStructure('Local Unit', 'UL-3');

        $user = $this->fixtures->createRawUser('dual@example.com');
        $user->setExternalId('00000456B');
        $this->fixtures->assignUserToStructure($user, $pegassStruct);
        $userId = $user->getId();

        // The same email appears in the Annuaire roster.
        $this->call('createUsers', $annuaire, $this->extractWith(['dual@example.com']));

        $this->em->clear();
        $reloaded = $this->em->getRepository(User::class)->find($userId);

        $structureIds = array_map(static function (Structure $s) {
            return $s->getExternalId();
        }, $reloaded->getStructures(false)->toArray());

        $this->assertSame('00000456B', $reloaded->getExternalId(), 'existing NIVOL must be untouched');
        $this->assertContains('UL-3', $structureIds, 'Pegass structure must be untouched');
        $this->assertContains('ANNU-3', $structureIds, 'Annuaire structure is added');

        // No duplicate user created for that email.
        $this->assertCount(1, $this->em->getRepository(User::class)->findBy(['username' => 'dual@example.com']));
    }
}
