<?php

namespace App\Tests\Manager;

use App\Entity\Volunteer;
use App\Manager\VolunteerManager;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * GdprManager is not a public service, so we test the anonymization
 * logic directly through VolunteerManager and the GdprManager class.
 */
class GdprManagerTest extends KernelTestCase
{
    /** @var \App\Manager\GdprManager */
    private $gdprManager;

    /** @var DataFixtures */
    private $fixtures;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $em;

    protected function setUp() : void
    {
        self::bootKernel();

        $this->em = self::$container->get('doctrine.orm.entity_manager');
        $this->fixtures = new DataFixtures(
            $this->em,
            self::$container->get('security.password_encoder')
        );

        // GdprManager is not a public service; instantiate it manually
        $volunteerManager = self::$container->get(VolunteerManager::class);
        $this->gdprManager = new \App\Manager\GdprManager($volunteerManager);
    }

    public function testAnonymizeSetsFirstNameToAnonymous()
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('GDPR-001', 'gdpr1@example.com');
        $volunteer->setFirstName('Jean');
        $volunteer->setLastName('Dupont');
        $this->em->persist($volunteer);
        $this->em->flush();

        $this->gdprManager->anonymize($volunteer);

        $this->assertSame('Anonymous', $volunteer->getFirstName());
    }

    public function testAnonymizeSetsLastNameToAnonymous()
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('GDPR-002', 'gdpr2@example.com');
        $volunteer->setFirstName('Marie');
        $volunteer->setLastName('Martin');
        $this->em->persist($volunteer);
        $this->em->flush();

        $this->gdprManager->anonymize($volunteer);

        $this->assertSame('Anonymous', $volunteer->getLastName());
    }

    public function testAnonymizeClearsEmail()
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('GDPR-003', 'gdpr3@example.com');

        $this->gdprManager->anonymize($volunteer);

        $this->assertNull($volunteer->getEmail());
    }

    public function testAnonymizeLocksVolunteer()
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('GDPR-004', 'gdpr4@example.com');

        $this->gdprManager->anonymize($volunteer);

        $this->assertTrue($volunteer->isLocked());
    }

    public function testAnonymizeDisablesVolunteer()
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('GDPR-005', 'gdpr5@example.com');

        $this->gdprManager->anonymize($volunteer);

        $this->assertFalse($volunteer->isEnabled());
    }

    public function testAnonymizeClearsPhones()
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('GDPR-006', 'gdpr6@example.com');

        $this->gdprManager->anonymize($volunteer);

        $this->assertCount(0, $volunteer->getPhones());
    }

    public function testAnonymizePreservesExternalId()
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('GDPR-007', 'gdpr7@example.com');

        $this->gdprManager->anonymize($volunteer);

        $this->assertSame('GDPR-007', $volunteer->getExternalId());
    }

    public function testAnonymizeIsPersisted()
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('GDPR-008', 'gdpr8@example.com');
        $volunteer->setFirstName('Pierre');
        $volunteer->setLastName('Durand');
        $this->em->persist($volunteer);
        $this->em->flush();

        $volunteerId = $volunteer->getId();

        $this->gdprManager->anonymize($volunteer);
        $this->em->clear();

        $refreshed = $this->em->find(Volunteer::class, $volunteerId);
        $this->assertSame('Anonymous', $refreshed->getFirstName());
        $this->assertSame('Anonymous', $refreshed->getLastName());
        $this->assertNull($refreshed->getEmail());
        $this->assertTrue($refreshed->isLocked());
        $this->assertFalse($refreshed->isEnabled());
    }
}
