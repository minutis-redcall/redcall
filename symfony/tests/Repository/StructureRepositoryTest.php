<?php

namespace App\Tests\Repository;

use App\Entity\Structure;
use App\Repository\StructureRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StructureRepositoryTest extends KernelTestCase
{
    /** @var StructureRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(Structure::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    // ── findOneByExternalId ──

    public function testFindOneByExternalId(): void
    {
        $structure = $this->fixtures->createStructure('Find By ExtId', 'FIND-EXT-001');

        $found = $this->repository->findOneByExternalId('FIND-EXT-001');
        $this->assertNotNull($found);
        $this->assertSame($structure->getId(), $found->getId());
    }

    public function testFindOneByExternalIdReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->repository->findOneByExternalId('NONEXISTENT-STRUCT'));
    }

    // ── findOneByName ──

    public function testFindOneByName(): void
    {
        $this->fixtures->createStructure('Unique Name Structure', 'UNS-001');

        // Structure.setName() uppercases the name
        $found = $this->repository->findOneByName('UNIQUE NAME STRUCTURE');
        $this->assertNotNull($found);
        $this->assertSame('UNIQUE NAME STRUCTURE', $found->getName());
    }

    public function testFindOneByNameReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->repository->findOneByName('DOES NOT EXIST'));
    }

    // ── searchAll ──

    public function testSearchAll(): void
    {
        $this->fixtures->createStructure('Searchable Alpha', 'SRCH-A-001');

        $results = $this->repository->searchAll('SEARCHABLE ALPHA', 100);

        $names = array_map(function (Structure $s) { return $s->getName(); }, $results);
        $this->assertContains('SEARCHABLE ALPHA', $names);
    }

    public function testSearchAllWithNoMatchReturnsEmpty(): void
    {
        $results = $this->repository->searchAll('XXXXXXXXX_NO_MATCH_999', 100);
        $this->assertEmpty($results);
    }

    public function testSearchAllWithNullCriteriaReturnsAll(): void
    {
        $this->fixtures->createStructure('All Results Struct', 'ALLRES-001');

        $results = $this->repository->searchAll(null, 1000);
        $this->assertNotEmpty($results);
    }

    // ── searchAllQueryBuilder ──

    public function testSearchAllQueryBuilderOnlyEnabled(): void
    {
        $this->fixtures->createStructure('Enabled Struct', 'EN-S-001', true);
        $this->fixtures->createStructure('Disabled Struct', 'DIS-S-001', false);

        $results = $this->repository->searchAllQueryBuilder(null, true)
            ->getQuery()->getResult();

        $names = array_map(function (Structure $s) { return $s->getName(); }, $results);
        $this->assertContains('ENABLED STRUCT', $names);
        $this->assertNotContains('DISABLED STRUCT', $names);
    }

    // ── searchForUserQueryBuilder ──

    public function testSearchForUserQueryBuilder(): void
    {
        $setup = $this->fixtures->createUserWithStructure(
            'sfu@test.com', 'SFU Structure', 'SFU-EXT-001'
        );

        $results = $this->repository->searchForUserQueryBuilder($setup['user'], null)
            ->getQuery()->getResult();

        $ids = array_map(function (Structure $s) { return $s->getId(); }, $results);
        $this->assertContains($setup['structure']->getId(), $ids);
    }

    public function testSearchForUserQueryBuilderWithCriteria(): void
    {
        $setup = $this->fixtures->createUserWithStructure(
            'sfuc@test.com', 'Criteria Match Struct', 'SFUC-EXT-001'
        );

        $results = $this->repository->searchForUserQueryBuilder($setup['user'], 'Criteria Match')
            ->getQuery()->getResult();

        $ids = array_map(function (Structure $s) { return $s->getId(); }, $results);
        $this->assertContains($setup['structure']->getId(), $ids);
    }

    // ── getStructuresForUserQueryBuilder ──

    public function testGetStructuresForUserQueryBuilder(): void
    {
        $setup = $this->fixtures->createUserWithStructure(
            'gs4u@test.com', 'GS4U Structure', 'GS4U-EXT-001'
        );

        $results = $this->repository->getStructuresForUserQueryBuilder($setup['user'])
            ->getQuery()->getResult();

        $ids = array_map(function (Structure $s) { return $s->getId(); }, $results);
        $this->assertContains($setup['structure']->getId(), $ids);
    }

    // ── findCallableStructuresForVolunteer ──

    public function testFindCallableStructuresForVolunteer(): void
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'callable@test.com', false, 'CALLABLE-001', 'Callable Struct', 'CALLABLE-EXT'
        );

        $results = $this->repository->findCallableStructuresForVolunteer($setup['volunteer']);

        $ids = array_map(function (Structure $s) { return $s->getId(); }, $results);
        $this->assertContains($setup['structure']->getId(), $ids);
    }

    // ── findCallableStructuresForStructure ──

    public function testFindCallableStructuresForStructure(): void
    {
        $structure = $this->fixtures->createStructure('Callable Parent', 'CPAR-EXT');

        $results = $this->repository->findCallableStructuresForStructure($structure);

        $ids = array_map(function (Structure $s) { return $s->getId(); }, $results);
        $this->assertContains($structure->getId(), $ids);
    }

    // ── getCampaignStructures ──

    public function testGetCampaignStructures(): void
    {
        $fullCampaign = $this->fixtures->createFullCampaign('campstr@test.com');
        $this->fixtures->assignVolunteerToStructure(
            $fullCampaign['volunteer'],
            $fullCampaign['structure']
        );

        $results = $this->repository->getCampaignStructures($fullCampaign['campaign']);

        $ids = array_map(function (Structure $s) { return $s->getId(); }, $results);
        $this->assertContains($fullCampaign['structure']->getId(), $ids);
    }

    // ── getVolunteerLocalCounts ──

    public function testGetVolunteerLocalCounts(): void
    {
        $structure = $this->fixtures->createStructure('Local Count', 'LC-EXT-001');
        $v = $this->fixtures->createStandaloneVolunteer('LC-VOL-001', 'lc@test.com');
        $this->fixtures->assignVolunteerToStructure($v, $structure);

        $results = $this->repository->getVolunteerLocalCounts([$structure->getId()]);

        $this->assertNotEmpty($results);
        $this->assertEquals(1, $results[0]['count']);
    }

    // ── getDescendantStructures ──

    public function testGetDescendantStructures(): void
    {
        $parent = $this->fixtures->createStructure('Parent', 'PARENT-EXT');

        $descendants = $this->repository->getDescendantStructures([$parent->getId()]);

        $this->assertContains($parent->getId(), $descendants);
    }

    // ── getStructureHierarchyForCurrentUser ──

    public function testGetStructureHierarchyForCurrentUser(): void
    {
        $setup = $this->fixtures->createUserWithStructure(
            'hier@test.com', 'Hier Structure', 'HIER-EXT-001'
        );

        $rows = $this->repository->getStructureHierarchyForCurrentUser($setup['user']);

        $this->assertNotEmpty($rows);
        $parentIds = array_column($rows, 'id');
        $this->assertContains($setup['structure']->getId(), $parentIds);
    }

    // ── searchAllForVolunteerQueryBuilder ──

    public function testSearchAllForVolunteerQueryBuilder(): void
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'safv@test.com', false, 'SAFV-001', 'SAFV Structure', 'SAFV-EXT'
        );

        $results = $this->repository->searchAllForVolunteerQueryBuilder(
            $setup['volunteer'], null, true
        )->getQuery()->getResult();

        $ids = array_map(function (Structure $s) { return $s->getId(); }, $results);
        $this->assertContains($setup['structure']->getId(), $ids);
    }
}
