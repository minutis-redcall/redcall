<?php

namespace App\Tests\Repository;

use App\Entity\Expirable;
use App\Repository\ExpirableRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ExpirableRepositoryTest extends KernelTestCase
{
    /** @var ExpirableRepository */
    private $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(Expirable::class);
    }

    private function createExpirable(\DateTimeInterface $expiresAt, string $uuid = null): Expirable
    {
        $expirable = new Expirable();
        $expirable->setUuid($uuid ?? bin2hex(random_bytes(16)));
        $expirable->setData(['key' => 'value']);
        $expirable->setCreatedAt(new \DateTime());
        $expirable->setExpiresAt($expiresAt);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($expirable);
        $em->flush();

        return $expirable;
    }

    // ── clearExpired ──

    public function testClearExpiredRemovesExpiredEntries(): void
    {
        $expired = $this->createExpirable(new \DateTime('-1 hour'));
        $id = $expired->getId();

        $this->repository->clearExpired();

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $this->assertNull($this->repository->find($id));
    }

    public function testClearExpiredKeepsNonExpiredEntries(): void
    {
        $valid = $this->createExpirable(new \DateTime('+1 hour'));
        $id = $valid->getId();

        $this->repository->clearExpired();

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $this->assertNotNull($this->repository->find($id));
    }

    public function testClearExpiredReturnsDeletionCount(): void
    {
        $this->createExpirable(new \DateTime('-1 hour'));
        $this->createExpirable(new \DateTime('-2 hours'));

        $count = $this->repository->clearExpired();
        $this->assertGreaterThanOrEqual(2, $count);
    }

    // ── basic CRUD ──

    public function testFindByUuid(): void
    {
        $uuid = 'test-uuid-' . bin2hex(random_bytes(4));
        $this->createExpirable(new \DateTime('+1 day'), $uuid);

        $found = $this->repository->findOneBy(['uuid' => $uuid]);
        $this->assertNotNull($found);
        $this->assertSame($uuid, $found->getUuid());
    }

    public function testSaveAndRemove(): void
    {
        $expirable = $this->createExpirable(new \DateTime('+1 day'));
        $id = $expirable->getId();

        $this->repository->remove($expirable);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $this->assertNull($this->repository->find($id));
    }
}
