<?php

namespace App\Tests\Manager;

use App\Entity\Communication;
use App\Entity\Cost;
use App\Entity\Message;
use App\Manager\CostManager;
use App\Repository\CostRepository;
use App\Tests\Fixtures\DataFixtures;
use Bundles\TwilioBundle\Entity\TwilioCall;
use Bundles\TwilioBundle\Entity\TwilioMessage;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CostManagerTest extends KernelTestCase
{
    /** @var CostManager */
    private $costManager;

    /** @var DataFixtures */
    private $fixtures;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $em;

    /** @var CostRepository */
    private $costRepository;

    protected function setUp() : void
    {
        self::bootKernel();

        $this->costManager = self::$container->get(CostManager::class);
        $this->em = self::$container->get('doctrine.orm.entity_manager');
        $this->costRepository = self::$container->get(CostRepository::class);
        $this->fixtures = new DataFixtures(
            $this->em,
            self::$container->get('security.password_encoder')
        );
    }

    private function createTwilioMessage(
        string $direction = TwilioMessage::DIRECTION_OUTBOUND,
        string $from = '+33600000001',
        string $to = '+33600000002',
        string $message = 'Test SMS',
        string $price = '0.05',
        string $unit = 'USD'
    ) : TwilioMessage {
        $twilioMessage = new TwilioMessage();
        $twilioMessage->setUuid(bin2hex(random_bytes(16)));
        $twilioMessage->setDirection($direction);
        $twilioMessage->setFromNumber($from);
        $twilioMessage->setToNumber($to);
        $twilioMessage->setMessage($message);
        $twilioMessage->setPrice($price);
        $twilioMessage->setUnit($unit);

        return $twilioMessage;
    }

    private function createTwilioCall(
        string $direction = TwilioCall::DIRECTION_OUTBOUND,
        string $from = '+33600000003',
        string $to = '+33600000004',
        ?string $message = 'Call body',
        string $price = '0.10',
        string $unit = 'USD'
    ) : TwilioCall {
        $twilioCall = new TwilioCall();
        $twilioCall->setUuid(bin2hex(random_bytes(16)));
        $twilioCall->setDirection($direction);
        $twilioCall->setFromNumber($from);
        $twilioCall->setToNumber($to);
        if ($message !== null) {
            $twilioCall->setMessage($message);
        }
        $twilioCall->setPrice($price);
        $twilioCall->setUnit($unit);

        return $twilioCall;
    }

    public function testSaveMessageCostWithoutMessage()
    {
        $twilioMessage = $this->createTwilioMessage();

        $this->costManager->saveMessageCost($twilioMessage);

        $costs = $this->costRepository->findAll();
        $this->assertNotEmpty($costs);

        $cost = end($costs);
        $this->assertSame(Cost::DIRECTION_OUTBOUND, $cost->getDirection());
        $this->assertSame('+33600000001', $cost->getFromNumber());
        $this->assertSame('+33600000002', $cost->getToNumber());
        $this->assertSame('Test SMS', $cost->getBody());
        $this->assertSame('0.05', $cost->getPrice());
        $this->assertSame('USD', $cost->getCurrency());
    }

    public function testSaveMessageCostInbound()
    {
        $twilioMessage = $this->createTwilioMessage(TwilioMessage::DIRECTION_INBOUND);

        $this->costManager->saveMessageCost($twilioMessage);

        $costs = $this->costRepository->findAll();
        $lastCost = end($costs);
        $this->assertSame(Cost::DIRECTION_INBOUND, $lastCost->getDirection());
    }

    public function testSaveMessageCostWithMessage()
    {
        $setup = $this->fixtures->createFullCampaign('costmsg@test.com', false, Communication::TYPE_SMS);
        $message = $setup['message'];

        $twilioMessage = $this->createTwilioMessage(
            TwilioMessage::DIRECTION_OUTBOUND,
            '+33600000001',
            '+33600000002',
            'Cost linked to message',
            '0.03',
            'EUR'
        );

        $this->costManager->saveMessageCost($twilioMessage, $message);

        $this->em->clear();
        $refreshedMessage = $this->em->find(Message::class, $message->getId());
        $this->assertGreaterThan(0, $refreshedMessage->getCosts()->count());

        $cost = $refreshedMessage->getCosts()->first();
        $this->assertSame('0.03', $cost->getPrice());
        $this->assertSame('EUR', $cost->getCurrency());
    }

    public function testSaveCallCostWithoutMessage()
    {
        $twilioCall = $this->createTwilioCall();

        $this->costManager->saveCallCost($twilioCall);

        $costs = $this->costRepository->findAll();
        $lastCost = end($costs);
        $this->assertSame(Cost::DIRECTION_OUTBOUND, $lastCost->getDirection());
        $this->assertSame('0.10', $lastCost->getPrice());
    }

    public function testSaveCallCostInbound()
    {
        $twilioCall = $this->createTwilioCall(TwilioCall::DIRECTION_INBOUND);

        $this->costManager->saveCallCost($twilioCall);

        $costs = $this->costRepository->findAll();
        $lastCost = end($costs);
        $this->assertSame(Cost::DIRECTION_INBOUND, $lastCost->getDirection());
    }

    public function testSaveCallCostWithNullMessage()
    {
        $twilioCall = $this->createTwilioCall(
            TwilioCall::DIRECTION_OUTBOUND,
            '+33600000005',
            '+33600000006',
            null,
            '0.07',
            'USD'
        );

        $this->costManager->saveCallCost($twilioCall);

        $costs = $this->costRepository->findAll();
        $lastCost = end($costs);
        $this->assertSame('', $lastCost->getBody());
    }

    public function testSaveCallCostWithAppMessage()
    {
        $setup = $this->fixtures->createFullCampaign('costcall@test.com', false, Communication::TYPE_CALL);
        $message = $setup['message'];

        $twilioCall = $this->createTwilioCall(
            TwilioCall::DIRECTION_OUTBOUND,
            '+33600000007',
            '+33600000008',
            'Call with message',
            '0.15',
            'EUR'
        );

        $this->costManager->saveCallCost($twilioCall, $message);

        $this->em->clear();
        $refreshedMessage = $this->em->find(Message::class, $message->getId());
        $this->assertGreaterThan(0, $refreshedMessage->getCosts()->count());
    }
}
