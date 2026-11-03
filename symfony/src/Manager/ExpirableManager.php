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

        if (null === $expirable || $expirable->isExpired()) {
            return null;
        }

        return $expirable->getData();
    }

    /**
     * Consumes one attempt on a still-valid expirable and returns its data
     * along with the number of attempts used so far (this one included), or
     * null when the expirable is missing, expired or out of attempts.
     *
     * @return array{data: mixed, attempts: int}|null
     */
    public function consumeAttempt(string $uuid, int $maxAttempts) : ?array
    {
        if (!$this->expirableRepository->incrementAttempts($uuid, $maxAttempts)) {
            return null;
        }

        $expirable = $this->expirableRepository->findOneByUuid($uuid);
        if (null === $expirable) {
            return null;
        }

        // The DQL UPDATE bypassed the identity map; reload the counter.
        $this->expirableRepository->refresh($expirable);

        return [
            'data'     => $expirable->getData(),
            'attempts' => $expirable->getAttempts(),
        ];
    }

    public function remove(string $uuid) : void
    {
        $this->expirableRepository->removeByUuid($uuid);
    }

    public function set($data, ?\DateTime $expiresAt = null) : string
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