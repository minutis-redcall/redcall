<?php

namespace App\Tests\Repository;

use App\Entity\Pegass;
use App\Repository\PegassRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PegassRepositoryTest extends KernelTestCase
{
    /** @var PegassRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(Pegass::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    // ── getEntities ──

    public function testGetEntities(): void
    {
        $this->fixtures->createPegass(Pegass::TYPE_VOLUNTEER, 'PEG-ENT-001', ['key' => 'val']);

        $entities = $this->repository->getEntities(Pegass::TYPE_VOLUNTEER);

        $identifiers = array_map(function (Pegass $p) { return $p->getIdentifier(); }, $entities);
        $this->assertContains('PEG-ENT-001', $identifiers);
    }

    public function testGetEntitiesFiltersByType(): void
    {
        $this->fixtures->createPegass(Pegass::TYPE_VOLUNTEER, 'PEG-VOL-001', ['data' => 'a']);
        $this->fixtures->createPegass(Pegass::TYPE_STRUCTURE, 'PEG-STR-001', ['data' => 'b']);

        $volEntities = $this->repository->getEntities(Pegass::TYPE_VOLUNTEER);
        $identifiers = array_map(function (Pegass $p) { return $p->getIdentifier(); }, $volEntities);
        $this->assertContains('PEG-VOL-001', $identifiers);
        $this->assertNotContains('PEG-STR-001', $identifiers);
    }

    // ── getEntity ──

    public function testGetEntity(): void
    {
        $this->fixtures->createPegass(Pegass::TYPE_VOLUNTEER, 'PEG-GET-001', ['x' => 1]);

        $entity = $this->repository->getEntity(Pegass::TYPE_VOLUNTEER, 'PEG-GET-001');
        $this->assertNotNull($entity);
        $this->assertSame('PEG-GET-001', $entity->getIdentifier());
    }

    public function testGetEntityReturnsNullForDisabledWhenOnlyEnabled(): void
    {
        $this->fixtures->createPegass(Pegass::TYPE_VOLUNTEER, 'PEG-DIS-001', ['x' => 1], false);

        $entity = $this->repository->getEntity(Pegass::TYPE_VOLUNTEER, 'PEG-DIS-001', true);
        $this->assertNull($entity);
    }

    public function testGetEntityReturnsDisabledWhenNotOnlyEnabled(): void
    {
        $this->fixtures->createPegass(Pegass::TYPE_VOLUNTEER, 'PEG-DIS-002', ['x' => 1], false);

        $entity = $this->repository->getEntity(Pegass::TYPE_VOLUNTEER, 'PEG-DIS-002', false);
        $this->assertNotNull($entity);
    }

    // ── findMissingEntities ──

    public function testFindMissingEntities(): void
    {
        $this->fixtures->createPegass(Pegass::TYPE_VOLUNTEER, 'PEG-KEEP-001', ['a' => 1]);
        $this->fixtures->createPegass(Pegass::TYPE_VOLUNTEER, 'PEG-MISS-001', ['b' => 2]);

        // PEG-MISS-001 is missing from the identifiers list => should be found
        $missing = $this->repository->findMissingEntities(
            Pegass::TYPE_VOLUNTEER,
            ['PEG-KEEP-001']
        );

        $identifiers = array_map(function (Pegass $p) { return $p->getIdentifier(); }, $missing);
        $this->assertContains('PEG-MISS-001', $identifiers);
        $this->assertNotContains('PEG-KEEP-001', $identifiers);
    }

    // ── findAllChildrenEntities ──

    public function testFindAllChildrenEntities(): void
    {
        $pegass = $this->fixtures->createPegass(Pegass::TYPE_VOLUNTEER, 'PEG-CHILD-001', ['c' => 1]);
        $pegass->setParentIdentifier('PARENT-001');
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($pegass);
        $em->flush();

        $children = $this->repository->findAllChildrenEntities(
            Pegass::TYPE_VOLUNTEER,
            'PARENT-001'
        );

        $identifiers = array_map(function (Pegass $p) { return $p->getIdentifier(); }, $children);
        $this->assertContains('PEG-CHILD-001', $identifiers);
    }

    // ── getAllEnabledEntities ──

    public function testGetAllEnabledEntities(): void
    {
        $this->fixtures->createPegass(Pegass::TYPE_VOLUNTEER, 'PEG-ENABLED-001', ['e' => 1], true);
        $this->fixtures->createPegass(Pegass::TYPE_VOLUNTEER, 'PEG-DISABLED-001', ['d' => 1], false);

        $results = $this->repository->getAllEnabledEntities();

        $identifiers = array_column($results, 'identifier');
        $this->assertContains('PEG-ENABLED-001', $identifiers);
        $this->assertNotContains('PEG-DISABLED-001', $identifiers);
    }

    // ── getEnabledEntitiesQueryBuilder ──

    public function testGetEnabledEntitiesQueryBuilder(): void
    {
        $this->fixtures->createPegass(Pegass::TYPE_STRUCTURE, 'PEG-EQBB-001', ['x' => 1]);

        $results = $this->repository->getEnabledEntitiesQueryBuilder(Pegass::TYPE_STRUCTURE, null)
            ->getQuery()->getResult();

        $identifiers = array_map(function (Pegass $p) { return $p->getIdentifier(); }, $results);
        $this->assertContains('PEG-EQBB-001', $identifiers);
    }

    public function testGetEnabledEntitiesQueryBuilderWithIdentifier(): void
    {
        $this->fixtures->createPegass(Pegass::TYPE_STRUCTURE, 'PEG-EQBI-001', ['x' => 1]);

        $results = $this->repository->getEnabledEntitiesQueryBuilder(Pegass::TYPE_STRUCTURE, 'PEG-EQBI-001')
            ->getQuery()->getResult();

        $this->assertCount(1, $results);
        $this->assertSame('PEG-EQBI-001', $results[0]->getIdentifier());
    }

    // ── save / delete ──

    public function testSaveAndDelete(): void
    {
        $pegass = new Pegass();
        $pegass->setType(Pegass::TYPE_VOLUNTEER);
        $pegass->setIdentifier('PEG-SAVE-001');
        $pegass->setContent(['saved' => true]);
        $pegass->setEnabled(true);

        $this->repository->save($pegass);

        $found = $this->repository->getEntity(Pegass::TYPE_VOLUNTEER, 'PEG-SAVE-001');
        $this->assertNotNull($found);

        $this->repository->delete($found);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $deleted = $this->repository->getEntity(Pegass::TYPE_VOLUNTEER, 'PEG-SAVE-001');
        $this->assertNull($deleted);
    }
}
