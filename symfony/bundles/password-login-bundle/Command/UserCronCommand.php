<?php

namespace Bundles\PasswordLoginBundle\Command;

use Bundles\PasswordLoginBundle\Base\BaseCommand;
use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Bundles\PasswordLoginBundle\Manager\CaptchaManager;
use Bundles\PasswordLoginBundle\Manager\EmailVerificationManager;
use Bundles\PasswordLoginBundle\Manager\PasswordRecoveryManager;
use Bundles\PasswordLoginBundle\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserCronCommand extends Command
{
    /**
     * @var CaptchaManager
     */
    private $captchaManager;

    /**
     * @var PasswordRecoveryManager
     */
    private $passwordRecoveryManager;

    /**
     * @var EmailVerificationManager
     */
    private $emailVerificationManager;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @param CaptchaManager           $captchaManager
     * @param PasswordRecoveryManager  $passwordRecoveryManager
     * @param EmailVerificationManager $emailVerificationManager
     * @param UserManager              $userManager
     */
    public function __construct(CaptchaManager $captchaManager,
        PasswordRecoveryManager $passwordRecoveryManager,
        EmailVerificationManager $emailVerificationManager,
        UserManager $userManager)
    {
        parent::__construct();

        $this->captchaManager           = $captchaManager;
        $this->passwordRecoveryManager  = $passwordRecoveryManager;
        $this->emailVerificationManager = $emailVerificationManager;
        $this->userManager              = $userManager;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('user:cron')
            ->setDescription('Cron used to cleanup user-related tables');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->captchaManager->clearExpired();
        $this->passwordRecoveryManager->clearExpired();

        foreach ($this->emailVerificationManager->getExpiredUsernames() as $username) {
            /** @var AbstractUser $user */
            $user = $this->userManager->findOneByUsername($username);

            if ($user && !$user->isTrusted() && !$user->isAdmin()) {
                $this->userManager->remove($user);
            }
        }

        $this->emailVerificationManager->clearExpired();

        return 0;
    }
}
