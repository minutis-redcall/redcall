<?php

namespace App\Tests\Repository;

use App\Entity\VolunteerList;
use App\Repository\VolunteerListRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class VolunteerListRepositoryTest extends KernelTestCase
{
    /** @var VolunteerListRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(VolunteerList::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    // ── findVolunteerListsForUser ──

    public function testFindVolunteerListsForUser(): void
    {
        $setup = $this->fixtures->createUserWithStructure('vluser@test.com', 'VL User Struct', 'VLUSER-EXT');
        $this->fixtures->createVolunteerList($setup['structure'], 'My List');

        $results = $this->repository->findVolunteerListsForUser($setup['user']);

        $names = array_map(function (VolunteerList $l) { return $l->getName(); }, $results);
        $this->assertContains('My List', $names);
    }

    public function testFindVolunteerListsForUserExcludesOtherStructures(): void
    {
        $setup = $this->fixtures->createUserWithStructure('vlexcl@test.com', 'VL Excl Struct', 'VLEXCL-EXT');
        $this->fixtures->createVolunteerList($setup['structure'], 'Accessible List');

        $otherStructure = $this->fixtures->createStructure('Other VL Struct', 'VLOTHER-EXT');
        $this->fixtures->createVolunteerList($otherStructure, 'Inaccessible List');

        $results = $this->repository->findVolunteerListsForUser($setup['user']);

        $names = array_map(function (VolunteerList $l) { return $l->getName(); }, $results);
        $this->assertContains('Accessible List', $names);
        $this->assertNotContains('Inaccessible List', $names);
    }

    public function testFindVolunteerListsForUserExcludesDisabledStructures(): void
    {
        $setup = $this->fixtures->createUserWithStructure('vldis@test.com', 'VL Dis Struct', 'VLDIS-EXT');
        $this->fixtures->createVolunteerList($setup['structure'], 'Active List');

        $disabledStructure = $this->fixtures->createStructure('Disabled VL Struct', 'VLDIS2-EXT', false);
        $this->fixtures->assignUserToStructure($setup['user'], $disabledStructure);
        $this->fixtures->createVolunteerList($disabledStructure, 'Disabled List');

        $results = $this->repository->findVolunteerListsForUser($setup['user']);

        $names = array_map(function (VolunteerList $l) { return $l->getName(); }, $results);
        $this->assertContains('Active List', $names);
        $this->assertNotContains('Disabled List', $names);
    }

    // ── save / remove (inherited from BaseRepository) ──

    public function testSaveAndRemove(): void
    {
        $structure = $this->fixtures->createStructure('VL Save Struct', 'VLSAVE-' . uniqid());

        $list = new VolunteerList();
        $list->setName('Saved List');
        $list->setStructure($structure);
        $list->setAudience([]);

        $this->repository->save($list);

        $listId = $list->getId();
        $found = $this->repository->find($listId);
        $this->assertNotNull($found);

        $this->repository->remove($found);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $this->assertNull($this->repository->find($listId));
    }
}
