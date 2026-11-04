<?php

namespace App\Tests\Manager;

use App\Entity\Expirable;
use App\Manager\ExpirableManager;
use App\Repository\ExpirableRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ExpirableManagerTest extends KernelTestCase
{
    private ExpirableManager $manager;
    private ExpirableRepository $repository;

    protected function setUp() : void
    {
        self::bootKernel();

        $container        = static::getContainer();
        $this->manager    = $container->get(ExpirableManager::class);
        $this->repository = $container->get('doctrine')->getRepository(Expirable::class);
    }

    public function testSetStoresDataAndReturnsUuid()
    {
        $data = ['key' => 'value', 'number' => 42];

        $uuid = $this->manager->set($data);

        $this->assertNotEmpty($uuid);
        $this->assertIsString($uuid);

        // UUID v4 format
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    public function testGetRetrievesStoredData()
    {
        $data = ['foo' => 'bar', 'nested' => ['a' => 1]];

        $uuid = $this->manager->set($data);
        $retrieved = $this->manager->get($uuid);

        $this->assertSame($data, $retrieved);
    }

    public function testGetReturnsNullForNonExistentUuid()
    {
        $result = $this->manager->get('non-existent-uuid');

        $this->assertNull($result);
    }

    public function testSetWithCustomExpirationDate()
    {
        $expiresAt = new \DateTime('+1 hour');
        $data = ['expires' => 'soon'];

        $uuid = $this->manager->set($data, $expiresAt);
        $retrieved = $this->manager->get($uuid);

        $this->assertSame($data, $retrieved);

        // Verify the expiration date was set correctly
        $expirable = $this->repository->findOneByUuid($uuid);
        $this->assertNotNull($expirable);
        // Allow 2 seconds of variance
        $this->assertEqualsWithDelta(
            $expiresAt->getTimestamp(),
            $expirable->getExpiresAt()->getTimestamp(),
            2
        );
    }

    public function testSetWithDefaultExpirationIsSevenDaysFromNow()
    {
        $before = new \DateTime();
        $uuid = $this->manager->set('test');
        $after = new \DateTime();

        $expirable = $this->repository->findOneByUuid($uuid);
        $this->assertNotNull($expirable);

        $expectedMin = (clone $before)->add(new \DateInterval('P7D'));
        $expectedMax = (clone $after)->add(new \DateInterval('P7D'));

        $this->assertGreaterThanOrEqual($expectedMin->getTimestamp(), $expirable->getExpiresAt()->getTimestamp());
        $this->assertLessThanOrEqual($expectedMax->getTimestamp(), $expirable->getExpiresAt()->getTimestamp());
    }

    public function testSetStoresStringData()
    {
        $uuid = $this->manager->set('simple string');
        $retrieved = $this->manager->get($uuid);

        $this->assertSame('simple string', $retrieved);
    }

    public function testSetStoresNullData()
    {
        $uuid = $this->manager->set(null);
        $retrieved = $this->manager->get($uuid);

        $this->assertNull($retrieved);
    }

    public function testClearExpiredRemovesExpiredEntries()
    {
        // Create an already-expired entry directly
        $expirable = new Expirable();
        $expirable->setUuid('expired-test-uuid');
        $expirable->setData('expired data');
        $expirable->setCreatedAt(new \DateTime('-2 days'));
        $expirable->setExpiresAt(new \DateTime('-1 day'));

        $this->manager->save($expirable);

        // Verify the row exists (get() hides it since it has expired)
        $this->assertNotNull($this->repository->findOneByUuid('expired-test-uuid'));

        // Clear expired
        $this->manager->clearExpired();

        // Verify it was removed
        $this->assertNull($this->repository->findOneByUuid('expired-test-uuid'));
    }

    public function testGetReturnsNullForExpiredEntryEvenBeforeCleanup()
    {
        // Regression (CERT-FR RM #1080870 / NIVOL brute force): get() used to
        // ignore expiresAt and rely on the clear:expirable cron only.
        $uuid = $this->manager->set(['code' => 'A00A00'], new \DateTime('-1 second'));

        $this->assertNotNull($this->repository->findOneByUuid($uuid));
        $this->assertNull($this->manager->get($uuid));
    }

    public function testConsumeAttemptCountsAndStopsAtMax()
    {
        $uuid = $this->manager->set(['code' => 'A00A00']);

        for ($i = 1; $i <= 3; $i++) {
            $attempt = $this->manager->consumeAttempt($uuid, 3);
            $this->assertNotNull($attempt);
            $this->assertSame($i, $attempt['attempts']);
            $this->assertSame(['code' => 'A00A00'], $attempt['data']);
        }

        $this->assertNull($this->manager->consumeAttempt($uuid, 3));
    }

    public function testConsumeAttemptReturnsNullWhenExpired()
    {
        $uuid = $this->manager->set(['code' => 'A00A00'], new \DateTime('-1 second'));

        $this->assertNull($this->manager->consumeAttempt($uuid, 10));
    }

    public function testConsumeAttemptReturnsNullForUnknownUuid()
    {
        $this->assertNull($this->manager->consumeAttempt('non-existent-uuid', 10));
    }

    public function testRemoveDeletesEntry()
    {
        $uuid = $this->manager->set('to delete');

        $this->manager->remove($uuid);

        $this->assertNull($this->repository->findOneByUuid($uuid));
    }

    public function testClearExpiredDoesNotRemoveNonExpiredEntries()
    {
        $uuid = $this->manager->set('still valid');

        $this->manager->clearExpired();

        $found = $this->manager->get($uuid);
        $this->assertSame('still valid', $found);
    }

    public function testMultipleEntriesAreIndependent()
    {
        $uuid1 = $this->manager->set(['entry' => 1]);
        $uuid2 = $this->manager->set(['entry' => 2]);

        $this->assertNotSame($uuid1, $uuid2);
        $this->assertSame(['entry' => 1], $this->manager->get($uuid1));
        $this->assertSame(['entry' => 2], $this->manager->get($uuid2));
    }
}
