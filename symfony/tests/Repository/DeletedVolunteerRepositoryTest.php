<?php

namespace App\Tests\Repository;

use App\Entity\DeletedVolunteer;
use App\Repository\DeletedVolunteerRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DeletedVolunteerRepositoryTest extends KernelTestCase
{
    /** @var DeletedVolunteerRepository */
    private $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(DeletedVolunteer::class);
    }

    // ── add ──

    public function testAdd(): void
    {
        $entity = new DeletedVolunteer();
        $entity->setHashedExternalId('hash-add-' . bin2hex(random_bytes(8)));

        $this->repository->add($entity);

        $this->assertNotNull($entity->getId());
        $this->assertNotNull($entity->getInsertedAt());

        $found = $this->repository->find($entity->getId());
        $this->assertNotNull($found);
    }

    public function testAddWithoutFlush(): void
    {
        $entity = new DeletedVolunteer();
        $entity->setHashedExternalId('hash-noflush-' . bin2hex(random_bytes(8)));

        $this->repository->add($entity, false);

        // ID may not be set yet because we didn't flush
        // But entity should be managed
        $em = self::$container->get('doctrine.orm.entity_manager');
        $this->assertTrue($em->contains($entity));

        $em->flush();
        $this->assertNotNull($entity->getId());
    }

    // ── remove ──

    public function testRemove(): void
    {
        $entity = new DeletedVolunteer();
        $entity->setHashedExternalId('hash-remove-' . bin2hex(random_bytes(8)));
        $this->repository->add($entity);

        $id = $entity->getId();

        $this->repository->remove($entity);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $this->assertNull($this->repository->find($id));
    }

    // ── findOneBy ──

    public function testFindOneByHashedExternalId(): void
    {
        $hash = 'hash-findone-' . bin2hex(random_bytes(8));
        $entity = new DeletedVolunteer();
        $entity->setHashedExternalId($hash);
        $this->repository->add($entity);

        $found = $this->repository->findOneBy(['hashedExternalId' => $hash]);
        $this->assertNotNull($found);
        $this->assertSame($hash, $found->getHashedExternalId());
    }

    public function testFindOneByReturnsNullForNonexistent(): void
    {
        $found = $this->repository->findOneBy(['hashedExternalId' => 'nonexistent-hash']);
        $this->assertNull($found);
    }

    // ── findAll ──

    public function testFindAll(): void
    {
        $entity = new DeletedVolunteer();
        $entity->setHashedExternalId('hash-findall-' . bin2hex(random_bytes(8)));
        $this->repository->add($entity);

        $all = $this->repository->findAll();
        $this->assertNotEmpty($all);
    }
}
