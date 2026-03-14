<?php

namespace App\Tests\Manager;

use App\Entity\Pegass;
use App\Manager\PegassManager;
use App\Repository\PegassRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PegassManagerTest extends KernelTestCase
{
    /** @var PegassManager */
    private $pegassManager;

    /** @var DataFixtures */
    private $fixtures;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $em;

    /** @var PegassRepository */
    private $pegassRepository;

    protected function setUp() : void
    {
        self::bootKernel();

        $this->pegassManager = self::$container->get(PegassManager::class);
        $this->em = self::$container->get('doctrine.orm.entity_manager');
        $this->pegassRepository = self::$container->get(PegassRepository::class);
        $this->fixtures = new DataFixtures(
            $this->em,
            self::$container->get('security.password_encoder')
        );
    }

    public function testCreateNewEntity()
    {
        $entity = $this->pegassManager->createNewEntity(
            Pegass::TYPE_STRUCTURE,
            '00001234',
            '|parent|'
        );

        $this->assertInstanceOf(Pegass::class, $entity);
        $this->assertSame(Pegass::TYPE_STRUCTURE, $entity->getType());
        $this->assertSame('00001234', $entity->getIdentifier());
        $this->assertSame('1234', $entity->getExternalId());
        $this->assertSame('|parent|', $entity->getParentIdentifier());
        $this->assertNotNull($entity->getId());
    }

    public function testCreateNewEntityVolunteerStripsLeadingZeros()
    {
        $entity = $this->pegassManager->createNewEntity(
            Pegass::TYPE_VOLUNTEER,
            '000000123456',
            '|struct|'
        );

        $this->assertSame('123456', $entity->getExternalId());
        $this->assertSame('000000123456', $entity->getIdentifier());
    }

    public function testGetEntityVolunteerPadsIdentifier()
    {
        // Create a volunteer entity with padded identifier
        $entity = $this->pegassManager->createNewEntity(
            Pegass::TYPE_VOLUNTEER,
            '000000000042',
            ''
        );
        $entity->setContent(['user' => ['id' => 42]]);
        $entity->setEnabled(true);
        $this->pegassManager->save($entity);

        // Get with unpadded identifier - should pad to 12 chars
        $found = $this->pegassManager->getEntity(Pegass::TYPE_VOLUNTEER, '42');

        $this->assertNotNull($found);
        $this->assertSame('000000000042', $found->getIdentifier());
    }

    public function testGetEntityStructure()
    {
        $entity = $this->pegassManager->createNewEntity(
            Pegass::TYPE_STRUCTURE,
            'STRUCT-001',
            ''
        );
        $entity->setContent(['structure' => ['id' => 'STRUCT-001', 'libelle' => 'Test']]);
        $entity->setEnabled(true);
        $this->pegassManager->save($entity);

        $found = $this->pegassManager->getEntity(Pegass::TYPE_STRUCTURE, 'STRUCT-001');

        $this->assertNotNull($found);
        $this->assertSame('STRUCT-001', $found->getIdentifier());
    }

    public function testGetEntityReturnsNullWhenNotFound()
    {
        $found = $this->pegassManager->getEntity(Pegass::TYPE_STRUCTURE, 'NONEXISTENT-999');

        $this->assertNull($found);
    }

    public function testGetEntityDisabledWithOnlyEnabled()
    {
        $entity = $this->pegassManager->createNewEntity(
            Pegass::TYPE_STRUCTURE,
            'DISABLED-001',
            ''
        );
        $entity->setEnabled(false);
        $this->pegassManager->save($entity);

        $found = $this->pegassManager->getEntity(Pegass::TYPE_STRUCTURE, 'DISABLED-001', true);
        $this->assertNull($found);

        $found = $this->pegassManager->getEntity(Pegass::TYPE_STRUCTURE, 'DISABLED-001', false);
        $this->assertNotNull($found);
    }

    public function testUpdateEntityStructure()
    {
        $entity = $this->pegassManager->createNewEntity(
            Pegass::TYPE_STRUCTURE,
            'UPD-STRUCT',
            ''
        );

        $content = [
            'structure' => ['id' => 'UPD-STRUCT', 'libelle' => 'Updated Structure'],
            'volunteers' => [],
        ];

        $this->pegassManager->updateEntity($entity, $content);

        $this->em->clear();
        $refreshed = $this->pegassRepository->find($entity->getId());

        $this->assertSame($content, $refreshed->getContent());
        $this->assertTrue($refreshed->getEnabled());
    }

    public function testUpdateEntityVolunteer()
    {
        $entity = $this->pegassManager->createNewEntity(
            Pegass::TYPE_VOLUNTEER,
            '000000999999',
            ''
        );

        $content = [
            'user' => ['id' => 999999, 'nom' => 'Test', 'prenom' => 'Volontaire'],
        ];

        $this->pegassManager->updateEntity($entity, $content);

        $this->em->clear();
        $refreshed = $this->pegassRepository->find($entity->getId());

        $this->assertSame($content, $refreshed->getContent());
        $this->assertTrue($refreshed->getEnabled());
    }

    public function testUpdateVolunteerWithNoContent()
    {
        $entity = $this->pegassManager->createNewEntity(
            Pegass::TYPE_VOLUNTEER,
            '000000888888',
            ''
        );

        // updateVolunteer with no content should be a no-op
        $this->pegassManager->updateVolunteer($entity);

        $this->assertNull($entity->getContent());
    }

    public function testRemoveMissingEntitiesDisablesThem()
    {
        $entity1 = $this->pegassManager->createNewEntity(
            Pegass::TYPE_STRUCTURE,
            'KEEP-001',
            ''
        );
        $entity1->setContent(['structure' => ['id' => 'KEEP-001']]);
        $entity1->setEnabled(true);
        $this->pegassManager->save($entity1);

        $entity2 = $this->pegassManager->createNewEntity(
            Pegass::TYPE_STRUCTURE,
            'REMOVE-001',
            ''
        );
        $entity2->setContent(['structure' => ['id' => 'REMOVE-001']]);
        $entity2->setEnabled(true);
        $this->pegassManager->save($entity2);

        // Remove entities that are NOT in the identifiers list
        $this->pegassManager->removeMissingEntities(Pegass::TYPE_STRUCTURE, ['KEEP-001']);

        $this->em->clear();

        $keptEntity = $this->pegassRepository->find($entity1->getId());
        $removedEntity = $this->pegassRepository->find($entity2->getId());

        $this->assertTrue($keptEntity->getEnabled());
        $this->assertFalse($removedEntity->getEnabled());
        $this->assertNull($removedEntity->getContent());
    }

    public function testSaveEntity()
    {
        $entity = new Pegass();
        $entity->setType(Pegass::TYPE_STRUCTURE);
        $entity->setIdentifier('SAVE-TEST');
        $entity->setExternalId('SAVE-TEST');
        $entity->setEnabled(true);

        $this->pegassManager->save($entity);

        $this->assertNotNull($entity->getId());
    }

    public function testDeleteEntity()
    {
        $entity = $this->pegassManager->createNewEntity(
            Pegass::TYPE_STRUCTURE,
            'DELETE-001',
            ''
        );
        $entityId = $entity->getId();

        $this->pegassManager->delete($entity);

        $this->assertNull($this->pegassRepository->find($entityId));
    }
}
