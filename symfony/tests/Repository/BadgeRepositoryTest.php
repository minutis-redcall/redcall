<?php

namespace App\Tests\Repository;

use App\Entity\Badge;
use App\Repository\BadgeRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BadgeRepositoryTest extends KernelTestCase
{
    /** @var BadgeRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(Badge::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    // ── findOneByExternalId ──

    public function testFindOneByExternalId(): void
    {
        $badge = $this->fixtures->createBadge('ExtId Badge', 'BADGE-FIND-001');

        $found = $this->repository->findOneByExternalId('BADGE-FIND-001');
        $this->assertNotNull($found);
        $this->assertSame($badge->getId(), $found->getId());
    }

    public function testFindOneByExternalIdReturnsNull(): void
    {
        $this->assertNull($this->repository->findOneByExternalId('NOPE-BADGE'));
    }

    // ── findOneByName ──

    public function testFindOneByName(): void
    {
        $this->fixtures->createBadge('Unique Badge Name', 'BADGE-NAME-001');

        $found = $this->repository->findOneByName('Unique Badge Name');
        $this->assertNotNull($found);
        $this->assertSame('Unique Badge Name', $found->getName());
    }

    public function testFindOneByNameReturnsNull(): void
    {
        $this->assertNull($this->repository->findOneByName('Nonexistent Badge'));
    }

    // ── getSearchInBadgesQueryBuilder ──

    public function testGetSearchInBadgesQueryBuilder(): void
    {
        $this->fixtures->createBadge('Searchable Badge', 'BADGE-SRCH-001');

        $results = $this->repository->getSearchInBadgesQueryBuilder('Searchable Badge')
            ->getQuery()->getResult();

        $names = array_map(function (Badge $b) { return $b->getName(); }, $results);
        $this->assertContains('Searchable Badge', $names);
    }

    public function testGetSearchInBadgesQueryBuilderWithNullCriteria(): void
    {
        $this->fixtures->createBadge('Any Badge', 'BADGE-ANY-001');

        $results = $this->repository->getSearchInBadgesQueryBuilder(null)
            ->getQuery()->getResult();

        $this->assertNotEmpty($results);
    }

    public function testGetSearchInBadgesQueryBuilderOnlyEnabled(): void
    {
        $this->fixtures->createBadge('Enabled Badge', 'BADGE-EN-001', true);
        $this->fixtures->createBadge('Disabled Badge', 'BADGE-DIS-001', false);

        $results = $this->repository->getSearchInBadgesQueryBuilder(null, true)
            ->getQuery()->getResult();

        $names = array_map(function (Badge $b) { return $b->getName(); }, $results);
        $this->assertContains('Enabled Badge', $names);
        $this->assertNotContains('Disabled Badge', $names);
    }

    // ── searchForCompletion ──

    public function testSearchForCompletion(): void
    {
        $badge = $this->fixtures->createBadge('Completion Badge', 'BADGE-COMP-001', true, true);

        $results = $this->repository->searchForCompletion('Completion', 100);

        $names = array_map(function (Badge $b) { return $b->getName(); }, $results);
        $this->assertContains('Completion Badge', $names);
    }

    public function testSearchForCompletionExcludesSynonyms(): void
    {
        $parent = $this->fixtures->createBadge('Parent Badge', 'BADGE-PAR-001', true, true);
        $synonym = $this->fixtures->createBadge('Synonym Badge', 'BADGE-SYN-001', true, true);
        $synonym->setSynonym($parent);
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($synonym);
        $em->flush();

        $results = $this->repository->searchForCompletion('Synonym', 100);

        $names = array_map(function (Badge $b) { return $b->getName(); }, $results);
        $this->assertNotContains('Synonym Badge', $names);
    }

    // ── searchNonVisibleUsableBadge ──

    public function testSearchNonVisibleUsableBadge(): void
    {
        $badge = $this->fixtures->createBadge('NonVisible Badge', 'BADGE-NV-001', true, false);

        $results = $this->repository->searchNonVisibleUsableBadge('NonVisible', 100);

        $names = array_map(function (Badge $b) { return $b->getName(); }, $results);
        $this->assertContains('NonVisible Badge', $names);
    }

    // ── getVolunteerCountInBadgeList ──

    public function testGetVolunteerCountInBadgeList(): void
    {
        $badge = $this->fixtures->createBadge('Count Badge', 'BADGE-CNT-001');
        $vol = $this->fixtures->createStandaloneVolunteer('BCNT-001', 'bcnt@test.com');
        // Badge-Volunteer is ManyToMany; Volunteer is the owning side
        $vol->addBadge($badge);
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($vol);
        $em->persist($badge);
        $em->flush();

        $counts = $this->repository->getVolunteerCountInBadgeList([$badge->getId()]);

        $this->assertArrayHasKey($badge->getId(), $counts);
        $this->assertEquals(1, $counts[$badge->getId()]);
    }

    // ── getPublicBadgesQueryBuilder ──

    public function testGetPublicBadgesQueryBuilder(): void
    {
        $this->fixtures->createBadge('Public Badge', 'BADGE-PUB-001', true, true);
        $this->fixtures->createBadge('Private Badge', 'BADGE-PRV-001', true, false);

        $results = $this->repository->getPublicBadgesQueryBuilder()
            ->getQuery()->getResult();

        $names = array_map(function (Badge $b) { return $b->getName(); }, $results);
        $this->assertContains('Public Badge', $names);
        $this->assertNotContains('Private Badge', $names);
    }

    // ── getBadgesInCategoryQueryBuilder ──

    public function testGetBadgesInCategoryQueryBuilder(): void
    {
        $data = $this->fixtures->createBadgeWithCategory('Cat Badge', 'Badge Category');

        $results = $this->repository->getBadgesInCategoryQueryBuilder($data['category'])
            ->getQuery()->getResult();

        $names = array_map(function (Badge $b) { return $b->getName(); }, $results);
        $this->assertContains('Cat Badge', $names);
    }

    // ── searchForVolunteerQueryBuilder ──

    public function testSearchForVolunteerQueryBuilder(): void
    {
        $badge = $this->fixtures->createBadge('Vol Badge', 'BADGE-VOL-001');
        $vol = $this->fixtures->createStandaloneVolunteer('BVOL-001', 'bvol@test.com');
        $vol->addBadge($badge);
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($vol);
        $em->persist($badge);
        $em->flush();

        $results = $this->repository->searchForVolunteerQueryBuilder($vol, null)
            ->getQuery()->getResult();

        $names = array_map(function (Badge $b) { return $b->getName(); }, $results);
        $this->assertContains('Vol Badge', $names);
    }

    // ── getNonVisibleUsableBadgesList ──

    public function testGetNonVisibleUsableBadgesList(): void
    {
        $badge = $this->fixtures->createBadge('NV List Badge', 'BADGE-NVL-001', true, false);

        $results = $this->repository->getNonVisibleUsableBadgesList([$badge->getId()]);

        $names = array_map(function (Badge $b) { return $b->getName(); }, $results);
        $this->assertContains('NV List Badge', $names);
    }
}
