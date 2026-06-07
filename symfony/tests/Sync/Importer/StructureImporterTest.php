<?php

namespace App\Tests\Sync\Importer;

use App\Entity\Structure;
use App\Manager\StructureManager;
use App\Sync\Dto\StructureRow;
use App\Sync\Importer\StructureImporter;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StructureImporterTest extends KernelTestCase
{
    private StructureImporter $importer;
    private StructureManager $structureManager;
    private \Doctrine\ORM\EntityManagerInterface $em;
    private DataFixtures $fixtures;

    protected function setUp() : void
    {
        self::bootKernel();

        $this->importer         = self::getContainer()->get(StructureImporter::class);
        $this->structureManager = self::getContainer()->get(StructureManager::class);
        $this->em               = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->fixtures         = new DataFixtures(
            $this->em,
            self::getContainer()->get('security.password_hasher')
        );
    }

    public function testCreatesNewStructure()
    {
        $row = new StructureRow(
            id: '4242',
            parentId: null,
            label: 'TEST UNIT',
            shortLabel: 'TU',
            address: '1 RUE TEST 75001 PARIS'
        );

        $this->importer->import($row);
        $this->em->clear();

        $structure = $this->structureManager->findOneByExternalId('4242');
        $this->assertNotNull($structure);
        $this->assertSame('TEST UNIT', $structure->getName());
        $this->assertSame('TU', $structure->getShortcut());
        $this->assertTrue($structure->isEnabled());
        $this->assertNotNull($structure->getLastSyncedAt());
    }

    public function testUpdatesExistingStructure()
    {
        $existing = $this->fixtures->createStructure('OLD NAME', '4242');
        $this->em->persist($existing);
        $this->em->flush();

        $row = new StructureRow(
            id: '4242',
            parentId: null,
            label: 'NEW NAME',
            shortLabel: 'NN',
            address: ''
        );

        $this->importer->import($row);
        $this->em->clear();

        $structure = $this->structureManager->findOneByExternalId('4242');
        $this->assertSame('NEW NAME', $structure->getName());
        $this->assertSame('NN', $structure->getShortcut());
    }

    public function testLockedStructureIsNotUpdated()
    {
        $existing = $this->fixtures->createStructure('LOCKED NAME', '4242');
        $existing->setLocked(true);
        $this->em->persist($existing);
        $this->em->flush();

        $row = new StructureRow(
            id: '4242',
            parentId: null,
            label: 'OVERWRITE ATTEMPT',
            shortLabel: 'OA',
            address: ''
        );

        $this->importer->import($row);
        $this->em->clear();

        $structure = $this->structureManager->findOneByExternalId('4242');
        $this->assertSame('LOCKED NAME', $structure->getName(), 'Locked structure should keep its name');
    }

    public function testLinksParentStructure()
    {
        $parent = $this->fixtures->createStructure('PARENT', '100');
        $this->em->persist($parent);
        $this->em->flush();

        $row = new StructureRow(
            id: '4242',
            parentId: '100',
            label: 'CHILD',
            shortLabel: 'C',
            address: ''
        );

        $this->importer->import($row);
        $this->em->clear();

        $structure = $this->structureManager->findOneByExternalId('4242');
        $this->assertNotNull($structure->getParentStructure());
        $this->assertSame('100', $structure->getParentStructure()->getExternalId());
    }

    public function testMissingParentIsSilentlyIgnored()
    {
        $row = new StructureRow(
            id: '4242',
            parentId: '999999',
            label: 'ORPHAN',
            shortLabel: 'O',
            address: ''
        );

        $this->importer->import($row);
        $this->em->clear();

        $structure = $this->structureManager->findOneByExternalId('4242');
        $this->assertNotNull($structure);
        $this->assertNull($structure->getParentStructure());
    }
}
