<?php

namespace Bundles\PasswordLoginBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A captcha is displayed if user failed to proceed on security-related forms FAILURE_GRACE times.
 *
 * Captcha is not displayed anymore to people sharing the same IP of a trusted user that recently
 * logged in: a user is trusted if it has been "activated" by an administrator.
 *
 * Exception: when a user updates his profile, captcha will be displayed without regarding at
 * whitelisting (because connected users are most likely trusted).
 *
 * @ORM\Table
 * @ORM\Entity(repositoryClass="Bundles\PasswordLoginBundle\Repository\CaptchaRepository")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Captcha
{
    const WHITELIST_EXPIRATION = '24 hours';
    const FAILURE_GRACE        = '3';

    /**
     * @ORM\Column(name="ip", type="integer", options={"unsigned"=true})
     * @ORM\Id
     */
    protected $ip;

    /**
     * @ORM\Column(name="timestamp", type="integer", options={"unsigned"=true})
     */
    protected $timestamp;

    /**
     * @ORM\Column(name="grace", type="integer")
     */
    protected $grace;

    /**
     * @ORM\Column(name="whitelisted", type="boolean")
     */
    protected $whitelisted;

    /**
     * Captcha constructor.
     *
     * @param string $ip
     */
    public function __construct(string $ip)
    {
        $this->timestamp   = time();
        $this->grace       = self::FAILURE_GRACE;
        $this->whitelisted = false;

        $this->setIp($ip);
    }

    /**
     * @return mixed
     */
    public function getIp(): string
    {
        return long2ip($this->ip);
    }

    /**
     * @param string $ip
     */
    public function setIp(string $ip)
    {
        $this->ip = ip2long($ip);
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @param int $timestamp
     */
    public function setTimestamp(int $timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return int
     */
    public function getGrace(): int
    {
        return $this->grace;
    }

    /**
     * @param int $grace
     */
    public function setGrace(int $grace)
    {
        $this->grace = $grace;
    }

    /**
     * @return bool
     */
    public function getWhitelisted(): bool
    {
        return $this->whitelisted;
    }

    /**
     * @param bool $whitelisted
     */
    public function setWhitelisted(bool $whitelisted): void
    {
        $this->whitelisted = $whitelisted;
    }

    /**
     * @return bool
     */
    public function isAllowed(): bool
    {
        return $this->isGracePeriod() || $this->whitelisted && !$this->hasExpired();
    }

    /**
     * @return bool
     */
    public function hasExpired(): bool
    {
        return $this->timestamp + strtotime(self::WHITELIST_EXPIRATION) - time() < time();
    }

    /**
     * @return bool
     */
    public function isGracePeriod(): bool
    {
        return $this->grace > 0;
    }
}