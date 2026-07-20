<?php

namespace App\Tests\Sync\Importer;

use App\Manager\BadgeManager;
use App\Sync\Importer\BadgeFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BadgeFactoryTest extends KernelTestCase
{
    private BadgeFactory $factory;
    private BadgeManager $badgeManager;
    private \Doctrine\ORM\EntityManagerInterface $em;

    protected function setUp() : void
    {
        self::bootKernel();

        $this->factory      = self::getContainer()->get(BadgeFactory::class);
        $this->badgeManager = self::getContainer()->get(BadgeManager::class);
        $this->em           = self::getContainer()->get('doctrine.orm.entity_manager');
    }

    private function item(string $externalId, string $name) : array
    {
        return [
            'externalId'  => $externalId,
            'name'        => $name,
            'description' => $name,
            'expiresAt'   => null,
        ];
    }

    /**
     * @return array[] 20 items matching 20 pre-created badges
     */
    private function createTwentyBadges() : array
    {
        $items = [];
        for ($i = 1; $i <= 20; $i++) {
            $this->factory->findOrCreate(sprintf('skill-test-%d', $i), sprintf('Skill %d', $i));
            $items[] = $this->item(sprintf('skill-test-%d', $i), sprintf('Skill %d', $i));
        }

        return $items;
    }

    public function testBulkUpsertCreatesAndUpdates()
    {
        $this->factory->bulkUpsert([
            $this->item('skill-test-created', 'Brand new'),
        ]);

        $badge = $this->badgeManager->findOneByExternalId('skill-test-created');
        $this->assertNotNull($badge);
        $this->assertSame('Brand new', $badge->getName());
    }

    public function testBulkUpsertAcceptsAFewRenames()
    {
        $items = $this->createTwentyBadges();

        // One rename out of 20 existing badges (5%): a legitimate label tweak
        $items[0]['name'] = 'Skill 1 (renamed)';
        $this->factory->bulkUpsert($items);

        $this->em->clear();
        $this->assertSame(
            'Skill 1 (renamed)',
            $this->badgeManager->findOneByExternalId('skill-test-1')->getName()
        );
    }

    public function testBulkUpsertRefusesMassRename()
    {
        // Regression test for the 2026-07-19 referential incident: the DSI
        // renumbered their competence ids, and ON DUPLICATE KEY UPDATE
        // silently repurposed hundreds of existing badge rows (new name, old
        // category/synonym/parent/visibility config). A run that renames a
        // large share of the badges it touches must abort instead.
        $items = $this->createTwentyBadges();

        for ($i = 0; $i < 10; $i++) {
            $items[$i]['name'] = sprintf('Totally different meaning %d', $i);
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/rename/i');

        $this->factory->bulkUpsert($items);
    }

    public function testBulkUpsertIgnoresAccentAndCaseOnlyRenames()
    {
        // The 2026-07-20 DSI referential writes labels without accents while
        // the legacy badges kept accented names ("Corps de Réserve de
        // l'Urgence" vs "Corps de Reserve de l'Urgence"). A rename that only
        // changes accents or case does not change the badge's meaning and
        // must not count toward the mass-rename guard.
        $items = [];
        for ($i = 1; $i <= 20; $i++) {
            $this->factory->findOrCreate(sprintf('skill-test-%d', $i), sprintf('Réservé à l\'Urgence %d', $i));
            $items[] = $this->item(sprintf('skill-test-%d', $i), sprintf('RESERVE A L\'URGENCE %d', $i));
        }

        $this->factory->bulkUpsert($items);

        $this->em->clear();
        $this->assertSame(
            "RESERVE A L'URGENCE 1",
            $this->badgeManager->findOneByExternalId('skill-test-1')->getName()
        );
    }

    public function testBulkUpsertMassRenameCanBeExplicitlyAllowed()
    {
        $items = $this->createTwentyBadges();

        for ($i = 0; $i < 10; $i++) {
            $items[$i]['name'] = sprintf('Assumed referential change %d', $i);
        }

        $this->factory->bulkUpsert($items, true);

        $this->em->clear();
        $this->assertSame(
            'Assumed referential change 0',
            $this->badgeManager->findOneByExternalId('skill-test-1')->getName()
        );
    }
}
