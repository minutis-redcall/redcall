<?php

namespace App\Tests\Manager;

use App\Entity\Structure;
use App\Manager\StructureManager;
use App\Tests\Fixtures\DataFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class StructureManagerTest extends KernelTestCase
{
    private StructureManager $manager;
    private DataFixtures $fixtures;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->manager = $container->get(StructureManager::class);
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
    // searchForCurrentUser
    // ──────────────────────────────────────────────

    public function testSearchForCurrentUserReturnsOnlyUserStructures(): void
    {
        $setup = $this->fixtures->createUserWithStructure('sm_user1@test.com', 'STRUCTURE ALPHA', 'SM-EXT-001');
        $this->fixtures->createStructure('STRUCTURE BETA', 'SM-EXT-002');

        $this->loginAs($setup['user']);

        $results = $this->manager->searchForCurrentUser(null, 100);

        $this->assertCount(1, $results);
        $this->assertSame($setup['structure']->getId(), $results[0]->getId());
    }

    public function testSearchForCurrentUserFiltersByCriteria(): void
    {
        $user = $this->fixtures->createRawUser('sm_user2@test.com');
        $structureA = $this->fixtures->createStructure('ALPHA STRUCTURE', 'SM-EXT-003');
        $structureB = $this->fixtures->createStructure('BETA STRUCTURE', 'SM-EXT-004');
        $this->fixtures->assignUserToStructure($user, $structureA);
        $this->fixtures->assignUserToStructure($user, $structureB);

        $this->loginAs($user);

        $results = $this->manager->searchForCurrentUser('ALPHA', 100);

        $this->assertCount(1, $results);
        $this->assertSame('ALPHA STRUCTURE', $results[0]->getName());
    }

    // ──────────────────────────────────────────────
    // searchForCurrentUserQueryBuilder
    // ──────────────────────────────────────────────

    public function testSearchForCurrentUserQueryBuilderReturnsQueryBuilder(): void
    {
        $setup = $this->fixtures->createUserWithStructure('sm_qb1@test.com', 'QBStruct', 'SM-EXT-005');
        $this->loginAs($setup['user']);

        $qb = $this->manager->searchForCurrentUserQueryBuilder(null, true);

        $this->assertInstanceOf(QueryBuilder::class, $qb);

        $results = $qb->getQuery()->getResult();
        $this->assertNotEmpty($results);
    }

    // ──────────────────────────────────────────────
    // searchQueryBuilder (admin vs non-admin)
    // ──────────────────────────────────────────────

    public function testSearchQueryBuilderReturnsAllForAdmin(): void
    {
        $setup = $this->fixtures->createAdminWithStructure('sm_admin1@test.com', 'ADMIN STRUCT', 'SM-EXT-006');
        $this->fixtures->createStructure('OTHER STRUCT', 'SM-EXT-007');

        $this->loginAs($setup['user']);

        $qb = $this->manager->searchQueryBuilder(null, true);
        $this->assertInstanceOf(QueryBuilder::class, $qb);

        $results = $qb->getQuery()->getResult();
        $this->assertGreaterThanOrEqual(2, count($results));
    }

    public function testSearchQueryBuilderReturnsOnlyUserStructuresForNonAdmin(): void
    {
        $setup = $this->fixtures->createUserWithStructure('sm_nonadmin1@test.com', 'MY STRUCT', 'SM-EXT-008');
        $this->fixtures->createStructure('NOT MY STRUCT', 'SM-EXT-009');

        $this->loginAs($setup['user']);

        $qb = $this->manager->searchQueryBuilder(null, true);
        $results = $qb->getQuery()->getResult();

        $ids = array_map(fn(Structure $s) => $s->getId(), $results);
        $this->assertContains($setup['structure']->getId(), $ids);
    }

    // ──────────────────────────────────────────────
    // searchAllForVolunteerQueryBuilder
    // ──────────────────────────────────────────────

    public function testSearchAllForVolunteerQueryBuilderReturnsQueryBuilder(): void
    {
        $structure = $this->fixtures->createStructure('VOL STRUCT', 'SM-EXT-010');
        $volunteer = $this->fixtures->createStandaloneVolunteer('SM-VOL-001', 'sm_vol1@test.com');
        $this->fixtures->assignVolunteerToStructure($volunteer, $structure);

        $qb = $this->manager->searchAllForVolunteerQueryBuilder($volunteer, null, true);

        $this->assertInstanceOf(QueryBuilder::class, $qb);

        $results = $qb->getQuery()->getResult();
        $this->assertNotEmpty($results);
    }

    public function testSearchAllForVolunteerQueryBuilderFiltersVolunteerStructures(): void
    {
        $structure = $this->fixtures->createStructure('VOL STRUCT A', 'SM-EXT-011');
        $otherStructure = $this->fixtures->createStructure('OTHER STRUCT', 'SM-EXT-012');
        $volunteer = $this->fixtures->createStandaloneVolunteer('SM-VOL-002', 'sm_vol2@test.com');
        $this->fixtures->assignVolunteerToStructure($volunteer, $structure);

        $qb = $this->manager->searchAllForVolunteerQueryBuilder($volunteer, null, true);
        $results = $qb->getQuery()->getResult();

        $ids = array_map(fn(Structure $s) => $s->getId(), $results);
        $this->assertContains($structure->getId(), $ids);
        $this->assertNotContains($otherStructure->getId(), $ids);
    }

    // ──────────────────────────────────────────────
    // searchForVolunteerAndCurrentUserQueryBuilder
    // ──────────────────────────────────────────────

    public function testSearchForVolunteerAndCurrentUserQueryBuilderReturnsQueryBuilder(): void
    {
        $user = $this->fixtures->createRawUser('sm_vcu1@test.com');
        $structure = $this->fixtures->createStructure('VCU STRUCT', 'SM-EXT-013');
        $volunteer = $this->fixtures->createStandaloneVolunteer('SM-VOL-003', 'sm_vcu_vol@test.com');
        $this->fixtures->assignUserToStructure($user, $structure);
        $this->fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->loginAs($user);

        $qb = $this->manager->searchForVolunteerAndCurrentUserQueryBuilder($volunteer, null, true);

        $this->assertInstanceOf(QueryBuilder::class, $qb);

        $results = $qb->getQuery()->getResult();
        $this->assertNotEmpty($results);
    }

    // ──────────────────────────────────────────────
    // searchForVolunteerQueryBuilder (admin vs non-admin)
    // ──────────────────────────────────────────────

    public function testSearchForVolunteerQueryBuilderUsesAllForAdmin(): void
    {
        $user = $this->fixtures->createRawUser('sm_vadmin1@test.com', 'password', true);
        $structure = $this->fixtures->createStructure('ADM VOL STRUCT', 'SM-EXT-014');
        $volunteer = $this->fixtures->createStandaloneVolunteer('SM-VOL-004', 'sm_vadm_vol@test.com');
        $this->fixtures->assignUserToStructure($user, $structure);
        $this->fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->loginAs($user);

        $qb = $this->manager->searchForVolunteerQueryBuilder($volunteer, null, true);
        $this->assertInstanceOf(QueryBuilder::class, $qb);

        $results = $qb->getQuery()->getResult();
        $this->assertNotEmpty($results);
    }

    public function testSearchForVolunteerQueryBuilderUsesCurrentUserForNonAdmin(): void
    {
        $user = $this->fixtures->createRawUser('sm_vnonadm1@test.com');
        $structure = $this->fixtures->createStructure('NA VOL STRUCT', 'SM-EXT-015');
        $volunteer = $this->fixtures->createStandaloneVolunteer('SM-VOL-005', 'sm_vna_vol@test.com');
        $this->fixtures->assignUserToStructure($user, $structure);
        $this->fixtures->assignVolunteerToStructure($volunteer, $structure);

        $this->loginAs($user);

        $qb = $this->manager->searchForVolunteerQueryBuilder($volunteer, null, true);
        $this->assertInstanceOf(QueryBuilder::class, $qb);

        $results = $qb->getQuery()->getResult();
        $this->assertNotEmpty($results);
    }

    // ──────────────────────────────────────────────
    // getStructuresForUser
    // ──────────────────────────────────────────────

    public function testGetStructuresForUserReturnsKeyedById(): void
    {
        $setup = $this->fixtures->createUserWithStructure('sm_gsu1@test.com', 'GSU STRUCT', 'SM-EXT-016');

        $structures = $this->manager->getStructuresForUser($setup['user']);

        $this->assertIsArray($structures);
        $this->assertArrayHasKey($setup['structure']->getId(), $structures);
        $this->assertInstanceOf(Structure::class, $structures[$setup['structure']->getId()]);
    }

    public function testGetStructuresForUserReturnsEmptyWhenNoStructures(): void
    {
        $user = $this->fixtures->createRawUser('sm_gsu2@test.com');

        $structures = $this->manager->getStructuresForUser($user);

        $this->assertIsArray($structures);
        $this->assertEmpty($structures);
    }

    public function testGetStructuresForUserReturnsMultipleStructures(): void
    {
        $user = $this->fixtures->createRawUser('sm_gsu3@test.com');
        $structA = $this->fixtures->createStructure('GSU A', 'SM-EXT-017');
        $structB = $this->fixtures->createStructure('GSU B', 'SM-EXT-018');
        $this->fixtures->assignUserToStructure($user, $structA);
        $this->fixtures->assignUserToStructure($user, $structB);

        $structures = $this->manager->getStructuresForUser($user);

        $this->assertCount(2, $structures);
        $this->assertArrayHasKey($structA->getId(), $structures);
        $this->assertArrayHasKey($structB->getId(), $structures);
    }

    // ──────────────────────────────────────────────
    // countRedCallUsersInPager
    // ──────────────────────────────────────────────

    public function testCountRedCallUsersInPagerReturnsCounts(): void
    {
        $rows = [
            ['structure_id' => 1, 'count' => 5],
            ['structure_id' => 2, 'count' => 10],
        ];

        $pager = $this->createMock(Pagerfanta::class);
        $pager->method('getIterator')->willReturn(new \ArrayIterator($rows));

        $counts = $this->manager->countRedCallUsersInPager($pager);

        $this->assertSame([1 => 5, 2 => 10], $counts);
    }

    public function testCountRedCallUsersInPagerReturnsEmptyForEmptyPager(): void
    {
        $pager = $this->createMock(Pagerfanta::class);
        $pager->method('getIterator')->willReturn(new \ArrayIterator([]));

        $counts = $this->manager->countRedCallUsersInPager($pager);

        $this->assertSame([], $counts);
    }

    // ──────────────────────────────────────────────
    // addStructureAndItsChildrenToVolunteer
    // ──────────────────────────────────────────────

    public function testAddStructureAndItsChildrenToVolunteer(): void
    {
        $parentStructure = $this->fixtures->createStructure('PARENT STRUCT', 'SM-EXT-019');
        $childStructure = $this->fixtures->createStructure('CHILD STRUCT', 'SM-EXT-020');
        $childStructure->setParentStructure($parentStructure);
        $parentStructure->addChildrenStructure($childStructure);
        $this->em->persist($childStructure);
        $this->em->persist($parentStructure);
        $this->em->flush();

        $volunteer = $this->fixtures->createStandaloneVolunteer('SM-VOL-006', 'sm_vol6@test.com');

        $this->manager->addStructureAndItsChildrenToVolunteer($volunteer, $parentStructure);

        $structureIds = array_map(fn(Structure $s) => $s->getId(), $volunteer->getStructures(false)->toArray());
        $this->assertContains($parentStructure->getId(), $structureIds);
        $this->assertContains($childStructure->getId(), $structureIds);
    }

    public function testAddStructureAndItsChildrenToVolunteerWithNoChildren(): void
    {
        $structure = $this->fixtures->createStructure('LONE STRUCT', 'SM-EXT-021');
        $volunteer = $this->fixtures->createStandaloneVolunteer('SM-VOL-007', 'sm_vol7@test.com');

        $this->manager->addStructureAndItsChildrenToVolunteer($volunteer, $structure);

        $structureIds = array_map(fn(Structure $s) => $s->getId(), $volunteer->getStructures(false)->toArray());
        $this->assertContains($structure->getId(), $structureIds);
    }
}
