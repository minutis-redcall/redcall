<?php

namespace Bundles\PasswordLoginBundle\Command;

use Bundles\PasswordLoginBundle\Base\BaseCommand;
use Bundles\PasswordLoginBundle\Entity\Captcha;
use Bundles\PasswordLoginBundle\Entity\EmailVerification;
use Bundles\PasswordLoginBundle\Entity\PasswordRecovery;
use Bundles\PasswordLoginBundle\Entity\User;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserCronCommand extends BaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('user:cron')
            ->setDescription('Cron used to cleanup user-related tables');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getManager(Captcha::class)->clearExpired();
        $this->getManager(PasswordRecovery::class)->clearExpired();

        foreach ($this->getManager(EmailVerification::class)->getExpiredUsernames() as $username) {
            /** @var User $user */
            $user = $this->getManager(User::class)->find($username);

            if ($user && !$user->isTrusted() && !$user->isAdmin()) {
                $this->getManager()->remove($user);
            }
        }
        $this->getManager()->flush();

        $this->getManager(EmailVerification::class)->clearExpired();

        return 0;
    }
}
