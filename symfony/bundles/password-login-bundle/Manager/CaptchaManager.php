<?php

namespace Bundles\PasswordLoginBundle\Manager;

use Bundles\PasswordLoginBundle\Entity\Captcha;
use Bundles\PasswordLoginBundle\Repository\CaptchaRepository;

class CaptchaManager
{
    /**
     * @var CaptchaRepository
     */
    private $captchaRepository;

    /**
     * @param CaptchaRepository $captchaRepository
     */
    public function __construct(CaptchaRepository $captchaRepository)
    {
        $this->captchaRepository = $captchaRepository;
    }

    public function clearExpired()
    {
        $this->captchaRepository->clearExpired();
    }

    public function isAllowed(string $ip) : bool
    {
        return $this->captchaRepository->isAllowed($ip);
    }

    public function isGracePeriod(string $ip) : bool
    {
        return $this->captchaRepository->isGracePeriod($ip);
    }

    public function decreaseGrace(string $ip)
    {
        $this->captchaRepository->decreaseGrace($ip);
    }

    public function whitelistNow(string $ip)
    {
        $this->captchaRepository->whitelistNow($ip);
    }

    public function save(Captcha $captcha)
    {
        $this->captchaRepository->save($captcha);
    }
}