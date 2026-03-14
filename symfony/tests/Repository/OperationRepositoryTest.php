<?php

namespace App\Tests\Repository;

use App\Entity\Operation;
use App\Repository\OperationRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OperationRepositoryTest extends KernelTestCase
{
    /** @var OperationRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(Operation::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    // ── findAll ──

    public function testFindAll(): void
    {
        $campaign = $this->fixtures->createCampaign('Op Campaign');
        $operation = $this->fixtures->createOperation($campaign, 11111);

        $found = $this->repository->find($operation->getId());
        $this->assertNotNull($found);
        $this->assertInstanceOf(Operation::class, $found);
    }

    // ── find ──

    public function testFind(): void
    {
        $campaign = $this->fixtures->createCampaign('Op Find Campaign');
        $operation = $this->fixtures->createOperation($campaign, 22222);

        $found = $this->repository->find($operation->getId());
        $this->assertNotNull($found);
        $this->assertSame(22222, $found->getOperationExternalId());
    }

    // ── save / remove (inherited from BaseRepository) ──

    public function testSaveAndRemove(): void
    {
        $campaign = $this->fixtures->createCampaign('Op Save Campaign');
        $operation = new Operation();
        $operation->setOperationExternalId(33333);
        $campaign->setOperation($operation);

        $em = self::$container->get('doctrine.orm.entity_manager');

        $this->repository->save($operation);

        $operationId = $operation->getId();
        $found = $this->repository->find($operationId);
        $this->assertNotNull($found);

        // Must detach campaign's FK reference before removing operation
        $campaign->setOperation(null);
        $em->persist($campaign);
        $em->flush();

        $this->repository->remove($found);

        $em->clear();
        $this->assertNull($this->repository->find($operationId));
    }
}
