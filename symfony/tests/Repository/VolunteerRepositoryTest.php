<?php

namespace App\Tests\Repository;

use App\Entity\Badge;
use App\Entity\Volunteer;
use App\Repository\VolunteerRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class VolunteerRepositoryTest extends KernelTestCase
{
    /** @var VolunteerRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(Volunteer::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    // ── findOneByExternalId ──

    public function testFindOneByExternalId(): void
    {
        // findOneByExternalId strips leading zeros from input before searching
        $volunteer = $this->fixtures->createStandaloneVolunteer('12345', 'ext@test.com');

        $found = $this->repository->findOneByExternalId('12345');
        $this->assertNotNull($found);
        $this->assertSame($volunteer->getId(), $found->getId());
    }

    public function testFindOneByExternalIdStripsLeadingZeros(): void
    {
        // Volunteer stored without leading zeros; lookup with zeros should find it
        $volunteer = $this->fixtures->createStandaloneVolunteer('12345', 'strip@test.com');

        $found = $this->repository->findOneByExternalId('00012345');
        $this->assertNotNull($found);
        $this->assertSame($volunteer->getId(), $found->getId());
    }

    public function testFindOneByExternalIdReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->repository->findOneByExternalId('NONEXISTENT'));
    }

    // ── disable / enable ──

    public function testDisable(): void
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('DIS-001', 'dis@test.com');
        $this->assertTrue($volunteer->isEnabled());

        $this->repository->disable($volunteer);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $fresh = $this->repository->find($volunteer->getId());
        $this->assertFalse($fresh->isEnabled());
    }

    public function testEnable(): void
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('EN-001', 'en@test.com');
        $volunteer->setEnabled(false);
        self::$container->get('doctrine.orm.entity_manager')->persist($volunteer);
        self::$container->get('doctrine.orm.entity_manager')->flush();

        $this->repository->enable($volunteer);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $fresh = $this->repository->find($volunteer->getId());
        $this->assertTrue($fresh->isEnabled());
    }

    // ── lock / unlock ──

    public function testLock(): void
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('LOCK-001', 'lock@test.com');
        $this->assertFalse($volunteer->isLocked());

        $this->repository->lock($volunteer);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $fresh = $this->repository->find($volunteer->getId());
        $this->assertTrue($fresh->isLocked());
    }

    public function testUnlock(): void
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('ULCK-001', 'ulck@test.com');
        $volunteer->setLocked(true);
        self::$container->get('doctrine.orm.entity_manager')->persist($volunteer);
        self::$container->get('doctrine.orm.entity_manager')->flush();

        $this->repository->unlock($volunteer);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $fresh = $this->repository->find($volunteer->getId());
        $this->assertFalse($fresh->isLocked());
    }

    // ── searchForUser ──

    public function testSearchForUserReturnsAccessibleVolunteers(): void
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'search@test.com', false, 'SRCH-001', 'Search Structure', 'SRCH-EXT'
        );

        $results = $this->repository->searchForUser($setup['user'], null, 100);

        $ids = array_map(function (Volunteer $v) { return $v->getId(); }, $results);
        $this->assertContains($setup['volunteer']->getId(), $ids);
    }

    public function testSearchForUserWithKeyword(): void
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'kw@test.com', false, 'KW-001', 'Keyword Structure', 'KW-EXT'
        );
        $setup['volunteer']->setFirstName('Alice');
        $setup['volunteer']->setLastName('Wonderland');
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($setup['volunteer']);
        $em->flush();

        $results = $this->repository->searchForUser($setup['user'], 'Alice', 100);

        $ids = array_map(function (Volunteer $v) { return $v->getId(); }, $results);
        $this->assertContains($setup['volunteer']->getId(), $ids);
    }

    public function testSearchForUserExcludesInaccessibleVolunteers(): void
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'excl@test.com', false, 'EXCL-001', 'Excl Structure', 'EXCL-EXT'
        );
        $outsider = $this->fixtures->createStandaloneVolunteer('OUTSIDE-001', 'outside@test.com');
        $otherStructure = $this->fixtures->createStructure('Other Structure', 'OTHER-EXT');
        $this->fixtures->assignVolunteerToStructure($outsider, $otherStructure);

        $results = $this->repository->searchForUser($setup['user'], null, 100);

        $ids = array_map(function (Volunteer $v) { return $v->getId(); }, $results);
        $this->assertNotContains($outsider->getId(), $ids);
    }

    // ── searchAll ──

    public function testSearchAll(): void
    {
        $v = $this->fixtures->createStandaloneVolunteer('SRCHALL-001', 'srchall@test.com');
        $v->setFirstName('UniqueSearchName');
        $v->setLastName('TestLast');
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($v);
        $em->flush();

        $results = $this->repository->searchAll('UniqueSearchName', 100);
        $this->assertNotEmpty($results);

        $ids = array_map(function (Volunteer $v) { return $v->getId(); }, $results);
        $this->assertContains($v->getId(), $ids);
    }

    public function testSearchAllReturnsEmptyForNonMatchingKeyword(): void
    {
        $results = $this->repository->searchAll('DEFINITELYNONEXISTENT999', 100);
        $this->assertEmpty($results);
    }

    // ── searchInStructureQueryBuilder ──

    public function testSearchInStructureQueryBuilder(): void
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'instruct@test.com', false, 'INSTRUC-001', 'In Structure', 'INSTRUC-EXT'
        );

        $qb = $this->repository->searchInStructureQueryBuilder($setup['structure'], null);
        $results = $qb->getQuery()->getResult();

        $ids = array_map(function (Volunteer $v) { return $v->getId(); }, $results);
        $this->assertContains($setup['volunteer']->getId(), $ids);
    }

    // ── getVolunteerList ──

    public function testGetVolunteerList(): void
    {
        $v1 = $this->fixtures->createStandaloneVolunteer('VL-001', 'vl1@test.com');
        $v2 = $this->fixtures->createStandaloneVolunteer('VL-002', 'vl2@test.com');

        $results = $this->repository->getVolunteerList([$v1->getId(), $v2->getId()]);

        $this->assertCount(2, $results);
    }

    public function testGetVolunteerListFiltersDisabledWhenOnlyEnabled(): void
    {
        $v1 = $this->fixtures->createStandaloneVolunteer('VLD-001', 'vld1@test.com');
        $v2 = $this->fixtures->createStandaloneVolunteer('VLD-002', 'vld2@test.com');
        $v2->setEnabled(false);
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($v2);
        $em->flush();

        $results = $this->repository->getVolunteerList([$v1->getId(), $v2->getId()], true);

        $ids = array_map(function (Volunteer $v) { return $v->getId(); }, $results);
        $this->assertContains($v1->getId(), $ids);
        $this->assertNotContains($v2->getId(), $ids);
    }

    // ── getVolunteerListForUser ──

    public function testGetVolunteerListForUser(): void
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'vlu@test.com', false, 'VLU-001', 'VLU Structure', 'VLU-EXT'
        );

        $results = $this->repository->getVolunteerListForUser(
            $setup['user'],
            [$setup['volunteer']->getId()]
        );

        $this->assertCount(1, $results);
    }

    // ── getVolunteerListInStructures ──

    public function testGetVolunteerListInStructures(): void
    {
        $structure = $this->fixtures->createStructure('VLS Structure', 'VLS-EXT');
        $v = $this->fixtures->createStandaloneVolunteer('VLS-001', 'vls@test.com');
        $this->fixtures->assignVolunteerToStructure($v, $structure);

        $results = $this->repository->getVolunteerListInStructures([$structure->getId()]);

        $ids = array_column($results, 'id');
        $this->assertContains($v->getId(), $ids);
    }

    // ── getVolunteerCountInStructures ──

    public function testGetVolunteerCountInStructures(): void
    {
        $structure = $this->fixtures->createStructure('CNT Structure', 'CNT-EXT');
        $v1 = $this->fixtures->createStandaloneVolunteer('CNT-001', 'cnt1@test.com');
        $v2 = $this->fixtures->createStandaloneVolunteer('CNT-002', 'cnt2@test.com');
        $this->fixtures->assignVolunteerToStructure($v1, $structure);
        $this->fixtures->assignVolunteerToStructure($v2, $structure);

        $count = $this->repository->getVolunteerCountInStructures([$structure->getId()]);

        $this->assertSame(2, $count);
    }

    // ── getIdsByExternalIds ──

    public function testGetIdsByExternalIds(): void
    {
        $v = $this->fixtures->createStandaloneVolunteer('EXTIDS-001', 'extids@test.com');

        $results = $this->repository->getIdsByExternalIds(['EXTIDS-001']);

        $ids = array_column($results, 'id');
        $this->assertContains($v->getId(), $ids);
    }

    public function testGetIdsByExternalIdsReturnsEmptyForUnknown(): void
    {
        $results = $this->repository->getIdsByExternalIds(['NOPE-99999']);
        $this->assertEmpty($results);
    }

    // ── filterInaccessibles ──

    public function testFilterInaccessibles(): void
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'filtr@test.com', false, 'FILTR-001', 'Filter Structure', 'FILTR-EXT'
        );
        $outsider = $this->fixtures->createStandaloneVolunteer('OUTSD-001', 'outsd@test.com');
        $otherStructure = $this->fixtures->createStructure('Other Filter', 'OFILTR-EXT');
        $this->fixtures->assignVolunteerToStructure($outsider, $otherStructure);

        $inaccessibles = $this->repository->filterInaccessibles(
            $setup['user'],
            [$setup['volunteer']->getId(), $outsider->getId()]
        );

        $this->assertContains($outsider->getId(), $inaccessibles);
        $this->assertNotContains($setup['volunteer']->getId(), $inaccessibles);
    }

    // ── filterInvalidExternalIds ──

    public function testFilterInvalidExternalIds(): void
    {
        $this->fixtures->createStandaloneVolunteer('VALID-001', 'valid@test.com');

        $invalid = $this->repository->filterInvalidExternalIds(['VALID-001', 'INVALID-999']);

        $this->assertContains('invalid-999', $invalid);
        $this->assertNotContains('valid-001', $invalid);
    }

    // ── filterDisabled ──

    public function testFilterDisabled(): void
    {
        $v1 = $this->fixtures->createStandaloneVolunteer('FILT-D-001', 'filtd1@test.com');
        $v2 = $this->fixtures->createStandaloneVolunteer('FILT-D-002', 'filtd2@test.com');
        $v2->setEnabled(false);
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($v2);
        $em->flush();

        $disabled = $this->repository->filterDisabled([$v1->getId(), $v2->getId()]);

        $ids = array_column($disabled, 'id');
        $this->assertContains($v2->getId(), $ids);
        $this->assertNotContains($v1->getId(), $ids);
    }

    // ── filterEmailMissing ──

    public function testFilterEmailMissing(): void
    {
        $v = $this->fixtures->createStandaloneVolunteer('NOMAIL-001', 'nomail@test.com');
        $v->setEmail(null);
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($v);
        $em->flush();

        $missing = $this->repository->filterEmailMissing([$v->getId()]);

        $this->assertNotEmpty($missing);
    }

    // ── filterEmailOptout ──

    public function testFilterEmailOptout(): void
    {
        $v = $this->fixtures->createStandaloneVolunteer('OPTOUT-001', 'optout@test.com');
        $v->setEmailOptin(false);
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($v);
        $em->flush();

        $optout = $this->repository->filterEmailOptout([$v->getId()]);

        $ids = array_column($optout, 'id');
        $this->assertContains($v->getId(), $ids);
    }

    // ── filterMinors ──

    public function testFilterMinors(): void
    {
        $v = $this->fixtures->createStandaloneVolunteer('MINOR-001', 'minor@test.com');
        $v->setMinor(true);
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($v);
        $em->flush();

        $minors = $this->repository->filterMinors([$v->getId()]);

        $this->assertNotEmpty($minors);
    }

    // ── getVolunteerCountInStructure ──

    public function testGetVolunteerCountInStructure(): void
    {
        $structure = $this->fixtures->createStructure('Count Struct', 'CNTSTR-EXT');
        $v1 = $this->fixtures->createStandaloneVolunteer('CNTSTR-001', 'cntstr1@test.com');
        $v2 = $this->fixtures->createStandaloneVolunteer('CNTSTR-002', 'cntstr2@test.com');
        $this->fixtures->assignVolunteerToStructure($v1, $structure);
        $this->fixtures->assignVolunteerToStructure($v2, $structure);

        $count = $this->repository->getVolunteerCountInStructure($structure);

        $this->assertSame(2, $count);
    }

    // ── getVolunteerGlobalCounts ──

    public function testGetVolunteerGlobalCounts(): void
    {
        $structure = $this->fixtures->createStructure('Global Count', 'GLBCNT-EXT');
        $v = $this->fixtures->createStandaloneVolunteer('GLBCNT-001', 'glbcnt@test.com');
        $this->fixtures->assignVolunteerToStructure($v, $structure);

        $count = $this->repository->getVolunteerGlobalCounts([$structure->getId()]);

        $this->assertGreaterThanOrEqual(1, $count);
    }

    // ── countActive ──

    public function testCountActive(): void
    {
        $this->fixtures->createStandaloneVolunteer('ACTIVE-001', 'active@test.com');

        $count = $this->repository->countActive();

        $this->assertGreaterThanOrEqual(1, $count);
    }

    // ── getIssues ──

    public function testGetIssues(): void
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'issues@test.com', false, 'ISSUE-001', 'Issue Structure', 'ISSUE-EXT'
        );
        // Volunteer has no phone and we clear email to create an "issue"
        $setup['volunteer']->setEmail(null);
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($setup['volunteer']);
        $em->flush();

        $issues = $this->repository->getIssues($setup['user']);

        $ids = array_map(function (Volunteer $v) { return $v->getId(); }, $issues);
        $this->assertContains($setup['volunteer']->getId(), $ids);
    }

    // ── getVolunteersFromList ──

    public function testGetVolunteersFromList(): void
    {
        $structure = $this->fixtures->createStructure('List Structure', 'LST-EXT');
        $v = $this->fixtures->createStandaloneVolunteer('LST-001', 'lst@test.com');
        $this->fixtures->assignVolunteerToStructure($v, $structure);

        $list = $this->fixtures->createVolunteerList($structure, 'Test Vol List', [$v]);

        $qb = $this->repository->getVolunteersFromList($list, null, false, false, false, []);
        $results = $qb->getQuery()->getResult();

        $ids = array_map(function (Volunteer $v) { return $v->getId(); }, $results);
        $this->assertContains($v->getId(), $ids);
    }
}
