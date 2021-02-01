<?php

namespace Bundles\PasswordLoginBundle\Repository;

use Bundles\PasswordLoginBundle\Base\BaseRepository;
use Bundles\PasswordLoginBundle\Entity\Captcha;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

class CaptchaRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Captcha::class);
    }

    public function clearExpired() : void
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
    public function isAllowed(string $ip) : bool
    {
        if (null === $captcha = $this->findOneBy(['ip' => ip2long($ip)])) {
            $captcha = new Captcha($ip);
        }

        return $captcha->isAllowed();
    }

    public function isGracePeriod(string $ip) : bool
    {
        if (null === $captcha = $this->findOneBy(['ip' => ip2long($ip)])) {
            $captcha = new Captcha($ip);
        }

        return $captcha->isGracePeriod();
    }

    /**
     * @param Captcha $captcha
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function decreaseGrace(string $ip) : void
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
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function whitelistNow(string $ip) : void
    {
        if (null === $captcha = $this->findOneBy(['ip' => ip2long($ip)])) {
            $captcha = new Captcha($ip);
        }

        $captcha->setWhitelisted(true);
        $captcha->setTimestamp(time());

        $this->save($captcha);
    }
}
