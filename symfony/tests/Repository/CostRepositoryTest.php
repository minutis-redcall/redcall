<?php

namespace App\Tests\Repository;

use App\Entity\Cost;
use App\Repository\CostRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CostRepositoryTest extends KernelTestCase
{
    /** @var CostRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(Cost::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    // ── save / find ──

    public function testSaveAndFind(): void
    {
        $fullCampaign = $this->fixtures->createFullCampaign('cost-save-' . uniqid() . '@test.com');
        $message = $fullCampaign['message'];

        $cost = new Cost();
        $cost->setMessage($message);
        $cost->setDirection(Cost::DIRECTION_OUTBOUND);
        $cost->setPrice('0.05');
        $cost->setCurrency('USD');
        $cost->setFromNumber('+33100000001');
        $cost->setToNumber('+33600000001');
        $cost->setBody('test');

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($cost);
        $em->flush();

        $this->assertNotNull($cost->getId());

        $found = $this->repository->find($cost->getId());
        $this->assertNotNull($found);
        $this->assertSame('0.05', $found->getPrice());
        $this->assertSame('USD', $found->getCurrency());
    }

    // ── save / remove (inherited from BaseRepository) ──

    public function testSaveAndRemove(): void
    {
        $fullCampaign = $this->fixtures->createFullCampaign('costsr-' . uniqid() . '@test.com');

        $cost = new Cost();
        $cost->setMessage($fullCampaign['message']);
        $cost->setDirection(Cost::DIRECTION_OUTBOUND);
        $cost->setPrice('0.10');
        $cost->setCurrency('EUR');
        $cost->setFromNumber('+33100000002');
        $cost->setToNumber('+33600000002');
        $cost->setBody('test body');

        $this->repository->save($cost);

        $costId = $cost->getId();
        $found = $this->repository->find($costId);
        $this->assertNotNull($found);

        $this->repository->remove($found);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $this->assertNull($this->repository->find($costId));
    }
}
