<?php

namespace Bundles\PasswordLoginBundle\Repository;

use Bundles\PasswordLoginBundle\Entity\Captcha;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;

class CaptchaRepository extends EntityRepository
{
    public function clearExpired(): void
    {
        $this->_em->createQuery('
            DELETE Bundles\PasswordLoginBundle\Entity\Captcha c
            WHERE c.timestamp < :expiry
        ')->execute([
            'expiry' => time() - (strtotime(Captcha::WHITELIST_EXPIRATION) - time()),
        ]);
    }

    /**
     * @param string $ip
     *
     * @return bool
     */
    public function isAllowed(string $ip): bool
    {
        if (null === $captcha = $this->findOneBy(['ip' => ip2long($ip)])) {
            $captcha = new Captcha($ip);
        }

        return $captcha->isAllowed();
    }

    public function isGracePeriod(string $ip): bool
    {
        if (null === $captcha = $this->findOneBy(['ip' => ip2long($ip)])) {
            $captcha = new Captcha($ip);
        }

        return $captcha->isGracePeriod();
    }

    /**
     * @param Captcha $captcha
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function decreaseGrace(string $ip): void
    {
        if (null === $captcha = $this->findOneBy(['ip' => ip2long($ip)])) {
            $captcha = new Captcha($ip);
        }

        $captcha->setGrace($captcha->getGrace() - 1);

        $this->save($captcha);
    }

    /**
     * @param string $ip
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function whitelistNow(string $ip): void
    {
        if (null === $captcha = $this->findOneBy(['ip' => ip2long($ip)])) {
            $captcha = new Captcha($ip);
        }

        $captcha->setWhitelisted(true);
        $captcha->setTimestamp(time());

        $this->save($captcha);
    }

    /**
     * @param Captcha $captcha
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function save(Captcha $captcha): void
    {
        if (UnitOfWork::STATE_MANAGED === $this->_em->getUnitOfWork()->getEntityState($captcha)) {
            $this->_em->merge($captcha);
        } else {
            $this->_em->persist($captcha);
        }

        $this->_em->flush($captcha);
    }
}
