<?php

namespace Bundles\PasswordLoginBundle\Repository;

use Bundles\PasswordLoginBundle\Base\BaseRepository;
use Bundles\PasswordLoginBundle\Entity\PasswordRecovery;
use Doctrine\Common\Persistence\ManagerRegistry;
use Ramsey\Uuid\Uuid;

class PasswordRecoveryRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordRecovery::class);
    }

    public function clearExpired(): void
    {
        $this->_em->createQuery('
                    DELETE Bundles\PasswordLoginBundle\Entity\PasswordRecovery pr
                    WHERE pr.timestamp < :expiry
                ')->execute([
            'expiry' => time() - (strtotime(PasswordRecovery::EXPIRATION) - time()),
        ]);
    }

    public function generateToken(string $username)
    {
        $passwordRecovery = $this->find($username);
        if ($passwordRecovery && !$passwordRecovery->hasExpired()) {
            return $passwordRecovery->getUuid();
        }

        if (!$passwordRecovery) {
            $passwordRecovery = new PasswordRecovery();
        }

        $passwordRecovery->setUsername($username);
        $passwordRecovery->setUuid($uuid = Uuid::uuid4());
        $passwordRecovery->setTimestamp(time());

        $this->save($passwordRecovery);

        return $uuid;
    }

    public function getByToken(string $token): ?PasswordRecovery
    {
        if (!$passwordRecovery = $this->findOneByUuid($token)) {
            return null;
        }

        if ($token !== $passwordRecovery->getUuid() || $passwordRecovery->hasExpired()) {
            return null;
        }

        return $passwordRecovery;
    }
}
