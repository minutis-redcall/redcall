<?php

namespace App\Tests\Manager;

use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Volunteer;
use App\Form\Model\SmsTrigger;
use App\Manager\CommunicationManager;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CommunicationManagerTest extends KernelTestCase
{
    /** @var CommunicationManager */
    private $communicationManager;

    /** @var DataFixtures */
    private $fixtures;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $em;

    protected function setUp() : void
    {
        self::bootKernel();

        $this->communicationManager = self::$container->get(CommunicationManager::class);
        $this->em = self::$container->get('doctrine.orm.entity_manager');
        $this->fixtures = new DataFixtures(
            $this->em,
            self::$container->get('security.password_encoder')
        );
    }

    public function testCreateNewCommunication()
    {
        $campaign = $this->fixtures->createCampaign('Comm Test Campaign');
        $communication = $this->fixtures->createCommunication($campaign, Communication::TYPE_SMS, 'Test body');

        $trigger = new SmsTrigger();
        $trigger->setLanguage('fr');
        $trigger->setMessage('Hello test');
        $trigger->setAnswers(['Yes', 'No']);
        $trigger->setAudience(['volunteers' => []]);

        $result = $this->communicationManager->createNewCommunication($campaign, $trigger, $communication);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Communication::class, $result);

        // The raw field should contain the trigger as JSON
        $this->assertNotNull($result->getRaw());
        $decoded = json_decode($result->getRaw(), true);
        $this->assertIsArray($decoded);

        // Communication should be linked to campaign
        $this->assertSame($campaign->getId(), $result->getCampaign()->getId());
    }

    public function testFindCommunicationIdsRequiringReports()
    {
        // Create a communication, then use native SQL to set dates because
        // the @PrePersist/@PreUpdate lifecycle callbacks overwrite lastActivityAt
        $campaign = $this->fixtures->createCampaign('Old Campaign');
        $communication = $this->fixtures->createCommunication($campaign, Communication::TYPE_SMS, 'Old body');
        $commId = $communication->getId();

        // Use native SQL to bypass lifecycle callbacks
        $conn = $this->em->getConnection();
        $twoDaysAgo = (new \DateTime('-2 days'))->format('Y-m-d H:i:s');
        $conn->executeUpdate(
            'UPDATE communication SET last_activity_at = ?, created_at = ?, report_id = NULL WHERE id = ?',
            [$twoDaysAgo, $twoDaysAgo, $commId]
        );
        $this->em->clear();

        $ids = $this->communicationManager->findCommunicationIdsRequiringReports();

        $this->assertIsArray($ids);
        $this->assertContains($commId, $ids);
    }

    public function testFindCommunicationIdsRequiringReportsExcludesRecent()
    {
        // Create a communication just now - should NOT be returned
        $campaign = $this->fixtures->createCampaign('Recent Campaign');
        $communication = $this->fixtures->createCommunication($campaign, Communication::TYPE_SMS, 'Recent body');

        $ids = $this->communicationManager->findCommunicationIdsRequiringReports();

        // A newly created communication should NOT need a report yet
        $this->assertNotContains($communication->getId(), $ids);
    }

    public function testFind()
    {
        $campaign = $this->fixtures->createCampaign('Find Test');
        $communication = $this->fixtures->createCommunication($campaign, Communication::TYPE_EMAIL, 'Find body');

        $found = $this->communicationManager->find($communication->getId());

        $this->assertNotNull($found);
        $this->assertSame($communication->getId(), $found->getId());
    }

    public function testFindReturnsNullForNonExistent()
    {
        $found = $this->communicationManager->find(999999);
        $this->assertNull($found);
    }

    public function testChangeName()
    {
        $campaign = $this->fixtures->createCampaign('Name Test');
        $communication = $this->fixtures->createCommunication($campaign, Communication::TYPE_SMS, 'Body');

        $this->communicationManager->changeName($communication, 'New Label');
        $this->em->clear();

        $refreshed = $this->communicationManager->find($communication->getId());
        $this->assertSame('New Label', $refreshed->getLabel());
    }

    public function testSaveCommunication()
    {
        $campaign = $this->fixtures->createCampaign('Save Test');
        $communication = $this->fixtures->createCommunication($campaign, Communication::TYPE_SMS, 'Save body');

        $communication->setBody('Updated body');
        $this->communicationManager->save($communication);
        $this->em->clear();

        $refreshed = $this->communicationManager->find($communication->getId());
        $this->assertSame('Updated body', $refreshed->getBody());
    }

    public function testGetCommunicationStructures()
    {
        // Build the graph manually with unique external IDs
        $user = $this->fixtures->createRawUser('commstruct@test.com', 'password', false);
        $volunteer = $this->fixtures->createVolunteer($user, 'COMMSTRUCT-VOL-001');
        $structure = $this->fixtures->createStructure('CommStruct Structure', 'COMMSTRUCT-EXT');
        $this->fixtures->assignUserToStructure($user, $structure);
        $this->fixtures->assignVolunteerToStructure($volunteer, $structure);

        $campaign = $this->fixtures->createCampaign('CommStruct Campaign');
        $communication = $this->fixtures->createCommunication($campaign, Communication::TYPE_SMS, 'Test body');
        $this->fixtures->createMessage($communication, $volunteer);

        $structureIds = $this->communicationManager->getCommunicationStructures($communication);

        $this->assertIsArray($structureIds);
        $this->assertContains($structure->getId(), $structureIds);
    }
}
