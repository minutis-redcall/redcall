<?php

namespace App\Tests\Manager;

use App\Entity\Volunteer;
use App\Manager\VolunteerManager;
use App\Tests\Fixtures\DataFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class VolunteerManagerTest extends KernelTestCase
{
    private VolunteerManager $manager;
    private DataFixtures $fixtures;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->manager = $container->get(VolunteerManager::class);
        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->fixtures = new DataFixtures(
            $this->em,
            $container->get('security.password_encoder')
        );
    }

    private function loginAs($user): void
    {
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        static::getContainer()->get('security.token_storage')->setToken($token);
    }

    // ──────────────────────────────────────────────
    // findOneByPhoneNumber
    // ──────────────────────────────────────────────

    public function testFindOneByPhoneNumberReturnsNullWhenNotFound(): void
    {
        $result = $this->manager->findOneByPhoneNumber('+33600000000');

        $this->assertNull($result);
    }

    // ──────────────────────────────────────────────
    // searchForCurrentUser
    // ──────────────────────────────────────────────

    public function testSearchForCurrentUserReturnsVolunteersInUserStructures(): void
    {
        $user = $this->fixtures->createRawUser('vm_scu1@test.com');
        $structure = $this->fixtures->createStructure('VM STRUCT 1', 'VM-EXT-001');
        $volunteer = $this->fixtures->createStandaloneVolunteer('VM-VOL-001', 'vm_scu1_v@test.com');
        $this->fixtures->assignUserToStructure($user, $structure);
        $this->fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->loginAs($user);

        $results = $this->manager->searchForCurrentUser(null, 100);

        $ids = array_map(fn(Volunteer $v) => $v->getId(), $results);
        $this->assertContains($volunteer->getId(), $ids);
    }

    public function testSearchForCurrentUserDoesNotReturnVolunteersOutsideUserStructures(): void
    {
        $user = $this->fixtures->createRawUser('vm_scu2@test.com');
        $structure = $this->fixtures->createStructure('VM STRUCT 2', 'VM-EXT-002');
        $volunteer = $this->fixtures->createStandaloneVolunteer('VM-VOL-002', 'vm_scu2_v@test.com');
        $this->fixtures->assignUserToStructure($user, $structure);
        $this->fixtures->assignVolunteerToStructure($volunteer, $structure);

        $otherStructure = $this->fixtures->createStructure('OTHER VM STRUCT', 'VM-EXT-003');
        $otherVol = $this->fixtures->createStandaloneVolunteer('VM-VOL-003', 'vm_other@test.com');
        $this->fixtures->assignVolunteerToStructure($otherVol, $otherStructure);

        $this->loginAs($user);

        $results = $this->manager->searchForCurrentUser(null, 100);

        $ids = array_map(fn(Volunteer $v) => $v->getId(), $results);
        $this->assertNotContains($otherVol->getId(), $ids);
    }

    // ──────────────────────────────────────────────
    // getVolunteerList
    // ──────────────────────────────────────────────

    public function testGetVolunteerListReturnsSortedByLastName(): void
    {
        $volA = $this->fixtures->createStandaloneVolunteer('VM-VOL-004', 'vm_vla@test.com');
        $volA->setFirstName('Alice');
        $volA->setLastName('Zebra');
        $this->em->persist($volA);

        $volB = $this->fixtures->createStandaloneVolunteer('VM-VOL-005', 'vm_vlb@test.com');
        $volB->setFirstName('Bob');
        $volB->setLastName('Alpha');
        $this->em->persist($volB);
        $this->em->flush();

        $list = $this->manager->getVolunteerList([$volA->getId(), $volB->getId()], false);

        $this->assertCount(2, $list);

        $names = array_map(fn(Volunteer $v) => $v->getLastName(), array_values($list));
        $this->assertSame('Alpha', $names[0]);
        $this->assertSame('Zebra', $names[1]);
    }

    public function testGetVolunteerListIsKeyedById(): void
    {
        $vol = $this->fixtures->createStandaloneVolunteer('VM-VOL-006', 'vm_vlk@test.com');
        $vol->setLastName('KeyTest');
        $this->em->persist($vol);
        $this->em->flush();

        $list = $this->manager->getVolunteerList([$vol->getId()], false);

        $this->assertArrayHasKey($vol->getId(), $list);
    }

    public function testGetVolunteerListReturnsEmptyForEmptyInput(): void
    {
        $list = $this->manager->getVolunteerList([]);

        $this->assertEmpty($list);
    }

    // ──────────────────────────────────────────────
    // getVolunteerListForCurrentUser
    // ──────────────────────────────────────────────

    public function testGetVolunteerListForCurrentUserReturnsAccessibleVolunteers(): void
    {
        $user = $this->fixtures->createRawUser('vm_vlu1@test.com');
        $structure = $this->fixtures->createStructure('VM STRUCT VLU', 'VM-EXT-004');
        $volunteer = $this->fixtures->createStandaloneVolunteer('VM-VOL-007', 'vm_vlu1_v@test.com');
        $this->fixtures->assignUserToStructure($user, $structure);
        $this->fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->loginAs($user);

        $results = $this->manager->getVolunteerListForCurrentUser([$volunteer->getId()]);

        $ids = array_map(fn(Volunteer $v) => $v->getId(), $results);
        $this->assertContains($volunteer->getId(), $ids);
    }

    // ──────────────────────────────────────────────
    // searchInStructureQueryBuilder
    // ──────────────────────────────────────────────

    public function testSearchInStructureQueryBuilderReturnsQueryBuilder(): void
    {
        $structure = $this->fixtures->createStructure('VM STRUCT SIS', 'VM-EXT-005');
        $volunteer = $this->fixtures->createStandaloneVolunteer('VM-VOL-008', 'vm_sis@test.com');
        $this->fixtures->assignVolunteerToStructure($volunteer, $structure);

        $qb = $this->manager->searchInStructureQueryBuilder($structure, null, false, false, false, false);

        $this->assertInstanceOf(QueryBuilder::class, $qb);

        $results = $qb->getQuery()->getResult();
        $ids = array_map(fn(Volunteer $v) => $v->getId(), $results);
        $this->assertContains($volunteer->getId(), $ids);
    }

    public function testSearchInStructureQueryBuilderWithHierarchy(): void
    {
        $parentStructure = $this->fixtures->createStructure('VM PARENT', 'VM-EXT-006');
        $childStructure = $this->fixtures->createStructure('VM CHILD', 'VM-EXT-007');
        $childStructure->setParentStructure($parentStructure);
        $parentStructure->addChildrenStructure($childStructure);
        $this->em->persist($childStructure);
        $this->em->persist($parentStructure);
        $this->em->flush();

        $childVol = $this->fixtures->createStandaloneVolunteer('VM-VOL-009', 'vm_childvol@test.com');
        $this->fixtures->assignVolunteerToStructure($childVol, $childStructure);

        $qb = $this->manager->searchInStructureQueryBuilder($parentStructure, null, false, false, true, false);

        $this->assertInstanceOf(QueryBuilder::class, $qb);

        $results = $qb->getQuery()->getResult();
        $ids = array_map(fn(Volunteer $v) => $v->getId(), $results);
        $this->assertContains($childVol->getId(), $ids);
    }

    // ──────────────────────────────────────────────
    // searchQueryBuilder (admin vs non-admin)
    // ──────────────────────────────────────────────

    public function testSearchQueryBuilderUsesAllForAdmin(): void
    {
        $user = $this->fixtures->createRawUser('vm_sqba@test.com', 'password', true);
        $this->loginAs($user);

        $qb = $this->manager->searchQueryBuilder(null, false, false, false);

        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }

    public function testSearchQueryBuilderUsesCurrentUserForNonAdmin(): void
    {
        $user = $this->fixtures->createRawUser('vm_sqbn@test.com');
        $structure = $this->fixtures->createStructure('VM STRUCT NA', 'VM-EXT-009');
        $volunteer = $this->fixtures->createStandaloneVolunteer('VM-VOL-011', 'vm_sqbn_v@test.com');
        $this->fixtures->assignUserToStructure($user, $structure);
        $this->fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->loginAs($user);

        $qb = $this->manager->searchQueryBuilder(null, false, false, false);

        $this->assertInstanceOf(QueryBuilder::class, $qb);

        $results = $qb->getQuery()->getResult();
        $ids = array_map(fn(Volunteer $v) => $v->getId(), $results);
        $this->assertContains($volunteer->getId(), $ids);
    }

    // ──────────────────────────────────────────────
    // searchAllQueryBuilder
    // ──────────────────────────────────────────────

    public function testSearchAllQueryBuilderReturnsQueryBuilder(): void
    {
        $this->fixtures->createStandaloneVolunteer('VM-VOL-012', 'vm_saqb@test.com');

        $qb = $this->manager->searchAllQueryBuilder(null, false, false, false);

        $this->assertInstanceOf(QueryBuilder::class, $qb);

        $results = $qb->getQuery()->getResult();
        $this->assertNotEmpty($results);
    }

    // ──────────────────────────────────────────────
    // searchForCurrentUserQueryBuilder
    // ──────────────────────────────────────────────

    public function testSearchForCurrentUserQueryBuilderReturnsQueryBuilder(): void
    {
        $user = $this->fixtures->createRawUser('vm_sfcuqb@test.com');
        $structure = $this->fixtures->createStructure('VM STRUCT SFCU', 'VM-EXT-010');
        $volunteer = $this->fixtures->createStandaloneVolunteer('VM-VOL-013', 'vm_sfcuqb_v@test.com');
        $this->fixtures->assignUserToStructure($user, $structure);
        $this->fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->loginAs($user);

        $qb = $this->manager->searchForCurrentUserQueryBuilder(null, false, false, false);

        $this->assertInstanceOf(QueryBuilder::class, $qb);

        $results = $qb->getQuery()->getResult();
        $ids = array_map(fn(Volunteer $v) => $v->getId(), $results);
        $this->assertContains($volunteer->getId(), $ids);
    }

    // ──────────────────────────────────────────────
    // getIdsByExternalIds
    // ──────────────────────────────────────────────

    public function testGetIdsByExternalIdsReturnsIds(): void
    {
        $vol = $this->fixtures->createStandaloneVolunteer('VM-VOL-014', 'vm_ids1@test.com');

        $ids = $this->manager->getIdsByExternalIds(['VM-VOL-014']);

        $this->assertContains($vol->getId(), $ids);
    }

    public function testGetIdsByExternalIdsStripsLeadingZeros(): void
    {
        $vol = $this->fixtures->createStandaloneVolunteer('12345', 'vm_ids2@test.com');

        $ids = $this->manager->getIdsByExternalIds(['00012345']);

        $this->assertContains($vol->getId(), $ids);
    }

    public function testGetIdsByExternalIdsReturnsEmptyForNonExistent(): void
    {
        $ids = $this->manager->getIdsByExternalIds(['NONEXISTENT-EXT-ID']);

        $this->assertEmpty($ids);
    }

    // ──────────────────────────────────────────────
    // filterInvalidExternalIds
    // ──────────────────────────────────────────────

    public function testFilterInvalidExternalIdsReturnsInvalid(): void
    {
        $this->fixtures->createStandaloneVolunteer('VM-VOL-015', 'vm_fie1@test.com');

        $invalid = $this->manager->filterInvalidExternalIds(['VM-VOL-015', 'INVALID-ONE']);

        $this->assertContains('invalid-one', $invalid);
        $this->assertNotContains('vm-vol-015', $invalid);
    }

    public function testFilterInvalidExternalIdsStripsLeadingZeros(): void
    {
        $this->fixtures->createStandaloneVolunteer('67890', 'vm_fie2@test.com');

        $invalid = $this->manager->filterInvalidExternalIds(['00067890']);

        $this->assertNotContains('67890', $invalid);
    }

    // ──────────────────────────────────────────────
    // filterInaccessibles
    // ──────────────────────────────────────────────

    public function testFilterInaccessiblesReturnsInaccessibleIds(): void
    {
        $user = $this->fixtures->createRawUser('vm_fi1@test.com');
        $structure = $this->fixtures->createStructure('VM STRUCT FI', 'VM-EXT-011');
        $volunteer = $this->fixtures->createStandaloneVolunteer('VM-VOL-016', 'vm_fi1_v@test.com');
        $this->fixtures->assignUserToStructure($user, $structure);
        $this->fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->loginAs($user);

        $otherStructure = $this->fixtures->createStructure('OTHER FI STRUCT', 'VM-EXT-012');
        $otherVol = $this->fixtures->createStandaloneVolunteer('VM-VOL-017', 'vm_fi_other@test.com');
        $this->fixtures->assignVolunteerToStructure($otherVol, $otherStructure);

        $inaccessible = $this->manager->filterInaccessibles([$volunteer->getId(), $otherVol->getId()]);

        $this->assertContains($otherVol->getId(), $inaccessible);
        $this->assertNotContains($volunteer->getId(), $inaccessible);
    }

    public function testFilterInaccessiblesReturnsEmptyWhenAllAccessible(): void
    {
        $user = $this->fixtures->createRawUser('vm_fi2@test.com');
        $structure = $this->fixtures->createStructure('VM STRUCT FI2', 'VM-EXT-013');
        $volunteer = $this->fixtures->createStandaloneVolunteer('VM-VOL-018', 'vm_fi2_v@test.com');
        $this->fixtures->assignUserToStructure($user, $structure);
        $this->fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->loginAs($user);

        $inaccessible = $this->manager->filterInaccessibles([$volunteer->getId()]);

        $this->assertEmpty($inaccessible);
    }

    // ──────────────────────────────────────────────
    // anonymize
    // ──────────────────────────────────────────────

    public function testAnonymizeClearsVolunteerData(): void
    {
        $vol = $this->fixtures->createStandaloneVolunteer('VM-VOL-019', 'vm_anon@test.com');
        $vol->setFirstName('John');
        $vol->setLastName('Doe');
        $this->em->persist($vol);
        $this->em->flush();

        $volId = $vol->getId();

        $this->manager->anonymize($vol);

        $this->em->clear();
        $reloaded = $this->em->getRepository(Volunteer::class)->find($volId);

        $this->assertNull($reloaded->getFirstName());
        $this->assertNull($reloaded->getLastName());
        $this->assertNull($reloaded->getEmail());
        $this->assertFalse($reloaded->isEnabled());
        $this->assertTrue($reloaded->isLocked());
    }

    public function testAnonymizeRemovesUserLink(): void
    {
        $user = $this->fixtures->createRawUser('vm_anonu@test.com');
        $vol = $this->fixtures->createVolunteer($user, 'VM-VOL-020', 'vm_anonu2@test.com');
        $vol->setFirstName('Jane');
        $vol->setLastName('Smith');
        $this->em->persist($vol);
        $this->em->flush();

        $volId = $vol->getId();

        $this->manager->anonymize($vol);

        $this->em->clear();
        $reloaded = $this->em->getRepository(Volunteer::class)->find($volId);

        $this->assertNull($reloaded->getUser());
    }

    // ──────────────────────────────────────────────
    // save
    // ──────────────────────────────────────────────

    public function testSaveUpdatesVolunteer(): void
    {
        $vol = $this->fixtures->createStandaloneVolunteer('VM-VOL-021', 'vm_save@test.com');
        $vol->setFirstName('SaveTest');
        $this->manager->save($vol);

        $this->em->clear();
        $reloaded = $this->em->getRepository(Volunteer::class)->find($vol->getId());

        $this->assertSame('SaveTest', $reloaded->getFirstName());
    }

    public function testSaveReturnsVolunteer(): void
    {
        $vol = $this->fixtures->createStandaloneVolunteer('VM-VOL-022', 'vm_saveret@test.com');

        $result = $this->manager->save($vol);

        $this->assertInstanceOf(Volunteer::class, $result);
        $this->assertSame($vol->getId(), $result->getId());
    }

    // ──────────────────────────────────────────────
    // orderVolunteerIdsByTriggeringPriority
    // ──────────────────────────────────────────────

    public function testOrderVolunteerIdsByTriggeringPriorityPutsNoBadgesAtEnd(): void
    {
        $volWithBadge = $this->fixtures->createStandaloneVolunteer('VM-VOL-025', 'vm_pri_c@test.com');
        $volNoBadge = $this->fixtures->createStandaloneVolunteer('VM-VOL-026', 'vm_pri_d@test.com');

        // The query requires a 4-level parent chain where all parents are enabled.
        // Create: p4 -> p3 -> p2 -> p1 -> badge
        $p4 = $this->fixtures->createBadge('P4 Badge', 'VM-BADGE-P4');
        $p3 = $this->fixtures->createBadge('P3 Badge', 'VM-BADGE-P3');
        $p3->setParent($p4);
        $this->em->persist($p3);

        $p2 = $this->fixtures->createBadge('P2 Badge', 'VM-BADGE-P2');
        $p2->setParent($p3);
        $this->em->persist($p2);

        $p1 = $this->fixtures->createBadge('P1 Badge', 'VM-BADGE-P1');
        $p1->setParent($p2);
        $this->em->persist($p1);

        $badge = $this->fixtures->createBadge('Leaf Badge', 'VM-BADGE-003');
        $badge->setTriggeringPriority(500);
        $badge->setParent($p1);
        $this->em->persist($badge);

        $volWithBadge->addBadge($badge);
        $this->em->persist($volWithBadge);
        $this->em->flush();

        $ordered = $this->manager->orderVolunteerIdsByTriggeringPriority([$volNoBadge->getId(), $volWithBadge->getId()]);

        // volWithBadge should come before volNoBadge
        $posWithBadge = array_search($volWithBadge->getId(), $ordered);
        $posNoBadge = array_search($volNoBadge->getId(), $ordered);
        $this->assertLessThan($posNoBadge, $posWithBadge);
    }

    public function testOrderVolunteerIdsByTriggeringPriorityReturnsAllIds(): void
    {
        $volA = $this->fixtures->createStandaloneVolunteer('VM-VOL-023A', 'vm_pri_a2@test.com');
        $volB = $this->fixtures->createStandaloneVolunteer('VM-VOL-024A', 'vm_pri_b2@test.com');

        $ordered = $this->manager->orderVolunteerIdsByTriggeringPriority([$volA->getId(), $volB->getId()]);

        $this->assertCount(2, $ordered);
        $this->assertContains($volA->getId(), $ordered);
        $this->assertContains($volB->getId(), $ordered);
    }

    // ──────────────────────────────────────────────
    // getVolunteersFromList
    // ──────────────────────────────────────────────

    public function testGetVolunteersFromListReturnsQueryBuilder(): void
    {
        $structure = $this->fixtures->createStructure('VM VL STRUCT', 'VM-EXT-014');
        $vol = $this->fixtures->createStandaloneVolunteer('VM-VOL-027', 'vm_vfl@test.com');
        $this->fixtures->assignVolunteerToStructure($vol, $structure);

        $list = $this->fixtures->createVolunteerList($structure, 'Test VL', [$vol]);

        $qb = $this->manager->getVolunteersFromList($list, null, false, false, false, []);

        $this->assertInstanceOf(QueryBuilder::class, $qb);

        $results = $qb->getQuery()->getResult();
        $ids = array_map(fn(Volunteer $v) => $v->getId(), $results);
        $this->assertContains($vol->getId(), $ids);
    }

    public function testGetVolunteersFromListFiltersByCriteria(): void
    {
        $structure = $this->fixtures->createStructure('VM VL STRUCT2', 'VM-EXT-015');
        $volAlice = $this->fixtures->createStandaloneVolunteer('VM-VOL-028', 'vm_vfl_alice@test.com');
        $volAlice->setFirstName('Alice');
        $volAlice->setLastName('Wonder');
        $this->em->persist($volAlice);

        $volBob = $this->fixtures->createStandaloneVolunteer('VM-VOL-029', 'vm_vfl_bob@test.com');
        $volBob->setFirstName('Bob');
        $volBob->setLastName('Builder');
        $this->em->persist($volBob);
        $this->em->flush();

        $this->fixtures->assignVolunteerToStructure($volAlice, $structure);
        $this->fixtures->assignVolunteerToStructure($volBob, $structure);

        $list = $this->fixtures->createVolunteerList($structure, 'Test VL2', [$volAlice, $volBob]);

        $qb = $this->manager->getVolunteersFromList($list, 'Alice', false, false, false, []);
        $results = $qb->getQuery()->getResult();

        $ids = array_map(fn(Volunteer $v) => $v->getId(), $results);
        $this->assertContains($volAlice->getId(), $ids);
        $this->assertNotContains($volBob->getId(), $ids);
    }
}
