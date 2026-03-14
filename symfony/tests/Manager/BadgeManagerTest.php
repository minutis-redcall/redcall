<?php

namespace App\Tests\Manager;

use App\Entity\Badge;
use App\Entity\Volunteer;
use App\Manager\BadgeManager;
use App\Tests\Fixtures\DataFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class BadgeManagerTest extends KernelTestCase
{
    private BadgeManager $manager;
    private DataFixtures $fixtures;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->manager = $container->get(BadgeManager::class);
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
    // getVolunteerCountInSearch
    // ──────────────────────────────────────────────

    public function testGetVolunteerCountInSearchReturnsCounts(): void
    {
        $badge1 = $this->fixtures->createBadge('Count Badge 1', 'BM-BADGE-001');
        $badge2 = $this->fixtures->createBadge('Count Badge 2', 'BM-BADGE-002');

        $vol1 = $this->fixtures->createStandaloneVolunteer('BM-VOL-001', 'bm_cnt1@test.com');
        $vol2 = $this->fixtures->createStandaloneVolunteer('BM-VOL-002', 'bm_cnt2@test.com');

        $vol1->addBadge($badge1);
        $vol2->addBadge($badge1);
        $vol2->addBadge($badge2);
        $this->em->persist($vol1);
        $this->em->persist($vol2);
        $this->em->flush();

        $pager = $this->createMock(Pagerfanta::class);
        $pager->method('getIterator')->willReturn(new \ArrayIterator([$badge1, $badge2]));

        $counts = $this->manager->getVolunteerCountInSearch($pager);

        $this->assertArrayHasKey($badge1->getId(), $counts);
        $this->assertArrayHasKey($badge2->getId(), $counts);
        $this->assertEquals(2, $counts[$badge1->getId()]);
        $this->assertEquals(1, $counts[$badge2->getId()]);
    }

    public function testGetVolunteerCountInSearchReturnsEmptyForEmptyPager(): void
    {
        $pager = $this->createMock(Pagerfanta::class);
        $pager->method('getIterator')->willReturn(new \ArrayIterator([]));

        $counts = $this->manager->getVolunteerCountInSearch($pager);

        $this->assertSame([], $counts);
    }

    public function testGetVolunteerCountInSearchWithBadgesHavingNoVolunteers(): void
    {
        $badge = $this->fixtures->createBadge('Lonely Badge', 'BM-BADGE-003');

        $pager = $this->createMock(Pagerfanta::class);
        $pager->method('getIterator')->willReturn(new \ArrayIterator([$badge]));

        $counts = $this->manager->getVolunteerCountInSearch($pager);

        // Badge has no volunteers, so it should not appear in counts
        $this->assertArrayNotHasKey($badge->getId(), $counts);
    }

    // ──────────────────────────────────────────────
    // getPublicBadges
    // ──────────────────────────────────────────────

    public function testGetPublicBadgesReturnsOnlyVisibleEnabledBadges(): void
    {
        $publicBadge = $this->fixtures->createBadge('Public Badge', 'BM-BADGE-004', true, true);
        $privateBadge = $this->fixtures->createBadge('Private Badge', 'BM-BADGE-005', true, false);

        $results = $this->manager->getPublicBadges();

        $ids = array_map(fn(Badge $b) => $b->getId(), $results);
        $this->assertContains($publicBadge->getId(), $ids);
        $this->assertNotContains($privateBadge->getId(), $ids);
    }

    public function testGetPublicBadgesExcludesDisabled(): void
    {
        $disabledPublic = $this->fixtures->createBadge('Disabled Public', 'BM-BADGE-006', false, true);

        $results = $this->manager->getPublicBadges();

        $ids = array_map(fn(Badge $b) => $b->getId(), $results);
        $this->assertNotContains($disabledPublic->getId(), $ids);
    }

    // ──────────────────────────────────────────────
    // getCustomOrPublicBadges
    // ──────────────────────────────────────────────

    public function testGetCustomOrPublicBadgesReturnsPublicWhenNoFavorites(): void
    {
        $user = $this->fixtures->createRawUser('bm_copb1@test.com');
        $this->loginAs($user);

        $publicBadge = $this->fixtures->createBadge('COP Public', 'BM-BADGE-007', true, true);

        $results = $this->manager->getCustomOrPublicBadges();

        $ids = array_map(fn(Badge $b) => $b->getId(), $results);
        $this->assertContains($publicBadge->getId(), $ids);
    }

    public function testGetCustomOrPublicBadgesReturnsFavoritesWhenUserHasThem(): void
    {
        $user = $this->fixtures->createRawUser('bm_copb2@test.com');

        $favBadge = $this->fixtures->createBadge('Fav Badge', 'BM-BADGE-008', true, true);
        $otherBadge = $this->fixtures->createBadge('Other Badge', 'BM-BADGE-009', true, true);

        $user->addFavoriteBadge($favBadge);
        $this->em->persist($user);
        $this->em->flush();

        $this->loginAs($user);

        $results = $this->manager->getCustomOrPublicBadges();

        $ids = array_map(fn(Badge $b) => $b->getId(), $results);
        $this->assertContains($favBadge->getId(), $ids);
        // Other badge should NOT appear since user has custom favorites
        $this->assertNotContains($otherBadge->getId(), $ids);
    }

    // ──────────────────────────────────────────────
    // searchForVolunteerQueryBuilder
    // ──────────────────────────────────────────────

    public function testSearchForVolunteerQueryBuilderReturnsQueryBuilder(): void
    {
        $vol = $this->fixtures->createStandaloneVolunteer('BM-VOL-003', 'bm_svqb@test.com');
        $badge = $this->fixtures->createBadge('Vol Badge', 'BM-BADGE-010');
        $vol->addBadge($badge);
        $this->em->persist($vol);
        $this->em->flush();

        $qb = $this->manager->searchForVolunteerQueryBuilder($vol, null);

        $this->assertInstanceOf(QueryBuilder::class, $qb);

        $results = $qb->getQuery()->getResult();
        $ids = array_map(fn(Badge $b) => $b->getId(), $results);
        $this->assertContains($badge->getId(), $ids);
    }

    public function testSearchForVolunteerQueryBuilderFiltersByCriteria(): void
    {
        $vol = $this->fixtures->createStandaloneVolunteer('BM-VOL-004', 'bm_svqbc@test.com');
        $badgeA = $this->fixtures->createBadge('Alpha Skill', 'BM-BADGE-011');
        $badgeB = $this->fixtures->createBadge('Beta Skill', 'BM-BADGE-012');
        $vol->addBadge($badgeA);
        $vol->addBadge($badgeB);
        $this->em->persist($vol);
        $this->em->flush();

        $qb = $this->manager->searchForVolunteerQueryBuilder($vol, 'Alpha');
        $results = $qb->getQuery()->getResult();

        $ids = array_map(fn(Badge $b) => $b->getId(), $results);
        $this->assertContains($badgeA->getId(), $ids);
        $this->assertNotContains($badgeB->getId(), $ids);
    }

    public function testSearchForVolunteerQueryBuilderReturnsEmptyWhenNoBadges(): void
    {
        $vol = $this->fixtures->createStandaloneVolunteer('BM-VOL-005', 'bm_svqbe@test.com');

        $qb = $this->manager->searchForVolunteerQueryBuilder($vol, null);
        $results = $qb->getQuery()->getResult();

        $this->assertEmpty($results);
    }

    // ──────────────────────────────────────────────
    // Additional coverage: getPublicBadgesQueryBuilder
    // ──────────────────────────────────────────────

    public function testGetPublicBadgesQueryBuilderReturnsQueryBuilder(): void
    {
        $qb = $this->manager->getPublicBadgesQueryBuilder();

        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }
}
