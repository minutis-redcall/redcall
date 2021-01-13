<?php

namespace App\Manager;

use App\Entity\Expirable;
use App\Repository\ExpirableRepository;
use Ramsey\Uuid\Uuid;

class ExpirableManager
{
    /**
     * @var ExpirableRepository
     */
    private $expirableRepository;

    public function __construct(ExpirableRepository $expirableRepository)
    {
        $this->expirableRepository = $expirableRepository;
    }

    public function get(string $uuid)
    {
        $expirable = $this->expirableRepository->findOneByUuid($uuid);

        if (null === $expirable) {
            return null;
        }

        return $expirable->getData();
    }

    public function set($data, \DateTime $expiresAt = null) : string
    {
        if (null === $expiresAt) {
            $expiresAt = (new \DateTime())->add(new \DateInterval('P7D'));
        }

        $expirable = new Expirable();
        $expirable->setUuid(Uuid::uuid4());
        $expirable->setData($data);
        $expirable->setCreatedAt(new \DateTime());
        $expirable->setExpiresAt($expiresAt);

        $this->expirableRepository->save($expirable);

        return $expirable->getUuid();
    }

    public function save(Expirable $expirable)
    {
        $this->expirableRepository->save($expirable);
    }

    public function clearExpired()
    {
        $this->expirableRepository->clearExpired();
    }
}