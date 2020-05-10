<?php

namespace Bundles\PasswordLoginBundle\Repository;

use Bundles\PasswordLoginBundle\Base\BaseRepository;
use Bundles\PasswordLoginBundle\Entity\EmailVerification;
use Doctrine\Common\Persistence\ManagerRegistry;
use Ramsey\Uuid\Uuid;

class EmailVerificationRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailVerification::class);
    }

    public function getExpiredUsernames(): array
    {
        return array_map(function (EmailVerification $ev) {
            return $ev->getUsername();
        }, $this->_em->createQuery('
            SELECT ev
            FROM Bundles\PasswordLoginBundle\Entity\EmailVerification ev
            WHERE ev.timestamp < :expiry
        ')->setParameter('expiry', time() - (strtotime(EmailVerification::EXPIRATION) - time()))
                     ->getResult()
        );
    }

    public function clearExpired(): void
    {
        $this->_em->createQuery('
            DELETE Bundles\PasswordLoginBundle\Entity\EmailVerification ev
            WHERE ev.timestamp < :expiry
        ')->execute([
            'expiry' => time() - strtotime(EmailVerification::EXPIRATION) - time(),
        ]);
    }

    public function generateToken(string $username, string $type)
    {
        $emailVerification = $this->find($username);
        if ($emailVerification && !$emailVerification->hasExpired()) {
            return $emailVerification->getUuid();
        }

        if (!$emailVerification) {
            $emailVerification = new EmailVerification();
        }

        $emailVerification->setUsername($username);
        $emailVerification->setUuid($uuid = Uuid::uuid4());
        $emailVerification->setType($type);
        $emailVerification->setTimestamp(time());

        $this->save($emailVerification);

        return $uuid;
    }

    public function getByToken(string $token)
    {
        if (!$emailVerification = $this->findOneByUuid($token)) {
            return;
        }

        if ($token !== $emailVerification->getUuid() || $emailVerification->hasExpired()) {
            return;
        }

        return $emailVerification;
    }
}
