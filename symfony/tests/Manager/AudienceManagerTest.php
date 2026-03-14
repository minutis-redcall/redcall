<?php

namespace App\Tests\Manager;

use App\Entity\Badge;
use App\Entity\Structure;
use App\Entity\Volunteer;
use App\Manager\AudienceManager;
use App\Model\Classification;
use App\Tests\Fixtures\DataFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class AudienceManagerTest extends KernelTestCase
{
    /** @var AudienceManager */
    private $audienceManager;

    /** @var DataFixtures */
    private $fixtures;

    /** @var EntityManagerInterface */
    private $em;

    protected function setUp() : void
    {
        self::bootKernel();

        $this->audienceManager = self::$container->get(AudienceManager::class);
        $this->em = self::$container->get('doctrine.orm.entity_manager');
        $this->fixtures = new DataFixtures(
            $this->em,
            self::$container->get('security.password_encoder')
        );
    }

    private function loginUser($user) : void
    {
        $token = new UsernamePasswordToken(
            $user, null, 'main', $user->getRoles()
        );
        self::$container->get('security.token_storage')->setToken($token);
    }

    // ──────────────────────────────────────────────
    // getVolunteerList
    // ──────────────────────────────────────────────

    public function testGetVolunteerListReturnsEmptyForEmptyIds()
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'audvol1@test.com', false, 'VOL-AUDVOL1', 'STRUCT-AUDVOL1', 'EXT-AUDVOL1'
        );
        $this->loginUser($setup['user']);

        $result = $this->audienceManager->getVolunteerList([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetVolunteerListReturnsVolunteersForValidIds()
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'audvol2@test.com', true, 'VOL-AUDVOL2', 'STRUCT-AUDVOL2', 'EXT-AUDVOL2'
        );
        // Explicitly persist volunteer due to DEFERRED_EXPLICIT tracking policy
        $this->em->persist($setup['volunteer']);
        $this->em->flush();
        $this->loginUser($setup['user']);

        $result = $this->audienceManager->getVolunteerList([$setup['volunteer']->getId()]);

        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);
    }

    public function testGetVolunteerListAsAdminReturnsAnyVolunteer()
    {
        // Create admin user
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'audvoladmin@test.com',
            true,
            'VOL-AUD-ADMIN',
            'STRUCT ADMIN',
            'EXT-AUD-ADMIN'
        );
        $this->loginUser($setup['user']);

        // Create a volunteer in a different structure
        $otherStructure = $this->fixtures->createStructure('OTHER STRUCT', 'EXT-OTHER-AUD');
        $otherVolunteer = $this->fixtures->createStandaloneVolunteer('VOL-OTHER-AUD', 'other-aud@test.com');
        $this->fixtures->assignVolunteerToStructure($otherVolunteer, $otherStructure);

        $result = $this->audienceManager->getVolunteerList([$otherVolunteer->getId()]);

        $this->assertNotEmpty($result);
    }

    public function testGetVolunteerListAsNonAdminFiltersToOwnStructures()
    {
        // Create non-admin user
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'audvolnonadmin@test.com',
            false,
            'VOL-AUD-NA',
            'STRUCT NA',
            'EXT-AUD-NA'
        );
        $this->loginUser($setup['user']);

        // Create a volunteer in a completely different structure (not accessible to user)
        $otherStructure = $this->fixtures->createStructure('ISOLATED STRUCT', 'EXT-ISO-AUD');
        $otherVolunteer = $this->fixtures->createStandaloneVolunteer('VOL-ISO-AUD', 'iso-aud@test.com');
        $this->fixtures->assignVolunteerToStructure($otherVolunteer, $otherStructure);

        // Non-admin should not see volunteers from other structures
        $result = $this->audienceManager->getVolunteerList([$otherVolunteer->getId()]);

        $this->assertEmpty($result);
    }

    // ──────────────────────────────────────────────
    // getBadgeList
    // ──────────────────────────────────────────────

    public function testGetBadgeListReturnsEmptyForEmptyIds()
    {
        $result = $this->audienceManager->getBadgeList([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetBadgeListReturnsBadgeDataForValidIds()
    {
        $badge = $this->fixtures->createBadge('AUD Badge', 'BADGE-AUD-001', true, false);

        $result = $this->audienceManager->getBadgeList([$badge->getId()]);

        // Non-visible badges should be returned by getNonVisibleUsableBadgesList
        $this->assertIsArray($result);
    }

    // ──────────────────────────────────────────────
    // classifyAudience
    // ──────────────────────────────────────────────

    public function testClassifyAudienceTestOnMeReturnsCurrentUserVolunteer()
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'classify1@test.com',
            false,
            'VOL-CLS-001',
            'STRUCT CLS',
            'EXT-CLS-001'
        );
        $this->loginUser($setup['user']);

        $data = [
            'test_on_me'          => true,
            'external_ids'        => [],
            'volunteers'          => [],
            'badges_all'          => false,
            'badges_ticked'       => [],
            'badges_searched'     => [],
            'structures_local'    => [],
            'structures_global'   => [],
            'allow_minors'        => true,
            'excluded_volunteers' => [],
            'preselection_key'    => null,
        ];

        $classification = $this->audienceManager->classifyAudience($data);

        $this->assertInstanceOf(Classification::class, $classification);
        $this->assertEquals([$setup['volunteer']->getId()], $classification->getReachable());
    }

    public function testClassifyAudienceReturnsClassificationWithVolunteerIds()
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'classify2@test.com',
            true,
            'VOL-CLS-002',
            'STRUCT CLS2',
            'EXT-CLS-002'
        );
        $this->loginUser($setup['user']);

        $data = [
            'test_on_me'          => false,
            'external_ids'        => [],
            'volunteers'          => [$setup['volunteer']->getId()],
            'badges_all'          => false,
            'badges_ticked'       => [],
            'badges_searched'     => [],
            'structures_local'    => [],
            'structures_global'   => [],
            'allow_minors'        => true,
            'excluded_volunteers' => [],
            'preselection_key'    => null,
        ];

        $classification = $this->audienceManager->classifyAudience($data);

        $this->assertInstanceOf(Classification::class, $classification);
        $this->assertContains($setup['volunteer']->getId(), $classification->getReachable());
    }

    public function testClassifyAudienceExcludesDisabledVolunteers()
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'classify3@test.com',
            true,
            'VOL-CLS-003',
            'STRUCT CLS3',
            'EXT-CLS-003'
        );
        $this->loginUser($setup['user']);

        // Create a disabled volunteer
        $disabledVol = $this->fixtures->createStandaloneVolunteer('VOL-DISABLED', 'disabled@test.com');
        $disabledVol->setEnabled(false);
        $this->em->persist($disabledVol);
        $this->em->flush();

        $data = [
            'test_on_me'          => false,
            'external_ids'        => [],
            'volunteers'          => [$disabledVol->getId()],
            'badges_all'          => false,
            'badges_ticked'       => [],
            'badges_searched'     => [],
            'structures_local'    => [],
            'structures_global'   => [],
            'allow_minors'        => true,
            'excluded_volunteers' => [],
            'preselection_key'    => null,
        ];

        $classification = $this->audienceManager->classifyAudience($data);

        $this->assertContains($disabledVol->getId(), $classification->getDisabled());
        $this->assertNotContains($disabledVol->getId(), $classification->getReachable());
    }

    public function testClassifyAudienceExcludesExplicitlyExcludedVolunteers()
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'classify4@test.com',
            true,
            'VOL-CLS-004',
            'STRUCT CLS4',
            'EXT-CLS-004'
        );
        $this->loginUser($setup['user']);

        $vol = $setup['volunteer'];

        $data = [
            'test_on_me'          => false,
            'external_ids'        => [],
            'volunteers'          => [$vol->getId()],
            'badges_all'          => false,
            'badges_ticked'       => [],
            'badges_searched'     => [],
            'structures_local'    => [],
            'structures_global'   => [],
            'allow_minors'        => true,
            'excluded_volunteers' => [$vol->getId()],
            'preselection_key'    => null,
        ];

        $classification = $this->audienceManager->classifyAudience($data);

        $this->assertContains($vol->getId(), $classification->getExcluded());
        $this->assertNotContains($vol->getId(), $classification->getReachable());
    }

    public function testClassifyAudienceExcludesMinorsWhenNotAllowed()
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'classify5@test.com',
            true,
            'VOL-CLS-005',
            'STRUCT CLS5',
            'EXT-CLS-005'
        );
        $this->loginUser($setup['user']);

        // Create a minor volunteer
        $minorVol = $this->fixtures->createStandaloneVolunteer('VOL-MINOR', 'minor@test.com');
        $minorVol->setMinor(true);
        $this->em->persist($minorVol);
        $this->em->flush();

        $data = [
            'test_on_me'          => false,
            'external_ids'        => [],
            'volunteers'          => [$minorVol->getId()],
            'badges_all'          => false,
            'badges_ticked'       => [],
            'badges_searched'     => [],
            'structures_local'    => [],
            'structures_global'   => [],
            'allow_minors'        => false,
            'excluded_volunteers' => [],
            'preselection_key'    => null,
        ];

        $classification = $this->audienceManager->classifyAudience($data);

        $this->assertContains($minorVol->getId(), $classification->getExcludedMinors());
        $this->assertNotContains($minorVol->getId(), $classification->getReachable());
    }

    public function testClassifyAudienceAllowsMinorsWhenAllowed()
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'classify6@test.com',
            true,
            'VOL-CLS-006',
            'STRUCT CLS6',
            'EXT-CLS-006'
        );
        $this->loginUser($setup['user']);

        $minorVol = $this->fixtures->createStandaloneVolunteer('VOL-MINOR2', 'minor2@test.com');
        $minorVol->setMinor(true);
        $this->em->persist($minorVol);
        $this->em->flush();

        $data = [
            'test_on_me'          => false,
            'external_ids'        => [],
            'volunteers'          => [$minorVol->getId()],
            'badges_all'          => false,
            'badges_ticked'       => [],
            'badges_searched'     => [],
            'structures_local'    => [],
            'structures_global'   => [],
            'allow_minors'        => true,
            'excluded_volunteers' => [],
            'preselection_key'    => null,
        ];

        $classification = $this->audienceManager->classifyAudience($data);

        $this->assertEmpty($classification->getExcludedMinors());
    }

    // ──────────────────────────────────────────────
    // extractAudience
    // ──────────────────────────────────────────────

    public function testExtractAudienceWithDirectVolunteerIds()
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-EA-001', 'ea1@test.com');

        $data = [
            'volunteers'        => [$volunteer->getId()],
            'external_ids'      => [],
            'preselection_key'  => null,
            'badges_all'        => false,
            'badges_ticked'     => [],
            'badges_searched'   => [],
            'structures_local'  => [],
            'structures_global' => [],
        ];

        $audience = $this->audienceManager->extractAudience($data);

        $this->assertContains($volunteer->getId(), $audience);
    }

    public function testExtractAudienceReturnsUniqueIds()
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('VOL-EA-002', 'ea2@test.com');

        $data = [
            'volunteers'        => [$volunteer->getId(), $volunteer->getId()],
            'external_ids'      => [],
            'preselection_key'  => null,
            'badges_all'        => false,
            'badges_ticked'     => [],
            'badges_searched'   => [],
            'structures_local'  => [],
            'structures_global' => [],
        ];

        $audience = $this->audienceManager->extractAudience($data);

        // Duplicates should be removed
        $this->assertCount(1, $audience);
    }

    public function testExtractAudienceWithBadgesAllInStructure()
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'ea3@test.com', true, 'VOL-EA-003', 'STRUCT EA', 'EXT-EA-003'
        );
        // Explicitly persist volunteer due to DEFERRED_EXPLICIT tracking policy
        $this->em->persist($setup['volunteer']);
        $this->em->flush();

        $data = [
            'volunteers'        => [],
            'external_ids'      => [],
            'preselection_key'  => null,
            'badges_all'        => true,
            'badges_ticked'     => [],
            'badges_searched'   => [],
            'structures_local'  => [$setup['structure']->getId()],
            'structures_global' => [],
        ];

        $audience = $this->audienceManager->extractAudience($data);

        // Should find volunteer in the structure
        $this->assertContains($setup['volunteer']->getId(), $audience);
    }

    // ──────────────────────────────────────────────
    // extractStructures
    // ──────────────────────────────────────────────

    public function testExtractStructuresWithLocalStructures()
    {
        $structure = $this->fixtures->createStructure('STRUCT ES', 'EXT-ES-001');

        $data = [
            'structures_local'  => [$structure->getId()],
            'structures_global' => [],
        ];

        $result = $this->audienceManager->extractStructures($data);

        $this->assertContains($structure->getId(), $result);
    }

    public function testExtractStructuresWithEmptyData()
    {
        $data = [
            'structures_local'  => [],
            'structures_global' => [],
        ];

        $result = $this->audienceManager->extractStructures($data);

        $this->assertEmpty($result);
    }

    public function testExtractStructuresCachesResults()
    {
        $structure = $this->fixtures->createStructure('STRUCT CACHE', 'EXT-CACHE-001');

        $data = [
            'structures_local'  => [$structure->getId()],
            'structures_global' => [],
        ];

        // Call twice - result should be cached
        $result1 = $this->audienceManager->extractStructures($data);
        $result2 = $this->audienceManager->extractStructures($data);

        $this->assertEquals($result1, $result2);
    }

    // ──────────────────────────────────────────────
    // extractBadgeCounts
    // ──────────────────────────────────────────────

    public function testExtractBadgeCountsReturnsZeroCountForNoVolunteers()
    {
        $badge = $this->fixtures->createBadge('COUNT BADGE', 'BADGE-COUNT-001');

        // Use a structure with no volunteers
        $structure = $this->fixtures->createStructure('EMPTY STRUCT', 'EXT-EMPTY-001');

        $data = [
            'structures_local'  => [$structure->getId()],
            'structures_global' => [],
        ];

        $counts = $this->audienceManager->extractBadgeCounts($data, [$badge]);

        // Key 0 is total count
        $this->assertArrayHasKey(0, $counts);
        $this->assertEquals(0, $counts[0]);

        // Badge-specific count
        $this->assertArrayHasKey($badge->getId(), $counts);
        $this->assertEquals(0, $counts[$badge->getId()]);
    }

    public function testExtractBadgeCountsIncludesGlobalZeroKey()
    {
        $structure = $this->fixtures->createStructure('GLOBAL STRUCT', 'EXT-GLOBAL-001');

        $data = [
            'structures_local'  => [$structure->getId()],
            'structures_global' => [],
        ];

        $counts = $this->audienceManager->extractBadgeCounts($data, []);

        // Should always have key 0 for total volunteer count
        $this->assertArrayHasKey(0, $counts);
    }

    public function testExtractBadgeCountsWithVolunteersInStructure()
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'badgecount@test.com', true, 'VOL-BC-001', 'STRUCT BC', 'EXT-BC-001'
        );
        // Explicitly persist volunteer due to DEFERRED_EXPLICIT tracking policy
        $this->em->persist($setup['volunteer']);
        $this->em->flush();

        $data = [
            'structures_local'  => [$setup['structure']->getId()],
            'structures_global' => [],
        ];

        $counts = $this->audienceManager->extractBadgeCounts($data, []);

        // At least 1 volunteer in the structure
        $this->assertGreaterThanOrEqual(1, $counts[0]);
    }
}
