<?php

namespace App\Tests\Manager;

use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Operation;
use App\Form\Model\SmsTrigger;
use App\Manager\OperationManager;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OperationManagerTest extends KernelTestCase
{
    /** @var OperationManager */
    private $operationManager;

    /** @var DataFixtures */
    private $fixtures;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $em;

    protected function setUp() : void
    {
        self::bootKernel();

        $this->operationManager = self::$container->get(OperationManager::class);
        $this->em = self::$container->get('doctrine.orm.entity_manager');
        $this->fixtures = new DataFixtures(
            $this->em,
            self::$container->get('security.password_encoder')
        );
    }

    public function testAddChoicesToOperationWithNoOperation()
    {
        // Campaign without operation - should be a no-op
        $campaign = $this->fixtures->createCampaign('No Op Campaign');
        $communication = $this->fixtures->createCommunication($campaign, Communication::TYPE_SMS, 'Test');
        $choice = $this->fixtures->createChoice($communication, 'Yes', '1');

        $trigger = new SmsTrigger();
        $trigger->setLanguage('fr');
        $trigger->setMessage('Test');
        $trigger->setAnswers(['Yes']);
        $trigger->addOperationAnswer('Yes');

        // This should not throw because operation is null
        $this->operationManager->addChoicesToOperation($communication, $trigger);

        // No exception is success
        $this->assertTrue(true);
    }

    public function testAddChoicesToOperationWithOperation()
    {
        $campaign = $this->fixtures->createCampaign('Op Campaign');
        $communication = $this->fixtures->createCommunication($campaign, Communication::TYPE_SMS, 'Test');
        $choice1 = $this->fixtures->createChoice($communication, 'Yes', '1');
        $choice2 = $this->fixtures->createChoice($communication, 'No', '2');

        $operation = $this->fixtures->createOperation($campaign, 99999);

        $trigger = new SmsTrigger();
        $trigger->setLanguage('fr');
        $trigger->setMessage('Test');
        $trigger->setAnswers(['Yes', 'No']);
        $trigger->addOperationAnswer('Yes');

        $this->operationManager->addChoicesToOperation($communication, $trigger);

        $this->em->clear();

        $refreshedOperation = $this->em->find(Operation::class, $operation->getId());
        $this->assertGreaterThanOrEqual(1, $refreshedOperation->getChoices()->count());
    }

    public function testAddResourceToOperationSkipsIfAlreadySet()
    {
        // If resourceExternalId is already set, it should be a no-op
        $setup = $this->fixtures->createFullCampaign('opres@test.com', false, Communication::TYPE_SMS);
        $message = $setup['message'];

        // Simulate already having a resourceExternalId
        $message->setResourceExternalId(12345);
        $this->em->persist($message);
        $this->em->flush();

        // This should return early without calling Minutis
        $this->operationManager->addResourceToOperation($message);

        // Resource should remain unchanged
        $this->assertSame(12345, $message->getResourceExternalId());
    }

    public function testRemoveResourceFromOperationSkipsIfNotSet()
    {
        $setup = $this->fixtures->createFullCampaign('opremove@test.com', false, Communication::TYPE_SMS);
        $message = $setup['message'];

        // No resourceExternalId set - should be a no-op
        $this->assertNull($message->getResourceExternalId());

        $this->operationManager->removeResourceFromOperation($message);

        // No exception means success
        $this->assertNull($message->getResourceExternalId());
    }
}
