<?php

namespace Bundles\PasswordLoginBundle\Repository;

use Bundles\PasswordLoginBundle\Entity\PasswordRecovery;
use Doctrine\ORM\EntityRepository;
use Ramsey\Uuid\Uuid;

class PasswordRecoveryRepository extends EntityRepository
{
    public function clearExpired(): void
    {
        $this->_em->createQuery('
                    DELETE Bundles\PasswordLoginBundle\Entity\PasswordRecovery pr
                    WHERE pr.timestamp < :expiry
                ')->execute([
            'expiry' => time() - (strtotime(PasswordRecovery::EXPIRATION) - time()),
        ]);
    }

    public function generateToken($username)
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

        $this->_em->persist($passwordRecovery);
        $this->_em->flush($passwordRecovery);

        return $uuid;
    }

    public function getUsernameByToken($token)
    {
        if (!$passwordRecovery = $this->findOneByUuid($token)) {
            return;
        }

        if ($token !== $passwordRecovery->getUuid() || $passwordRecovery->hasExpired()) {
            return;
        }

        return $passwordRecovery;
    }
}
