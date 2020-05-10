<?php

namespace Bundles\PasswordLoginBundle\Command;

use Bundles\PasswordLoginBundle\Base\BaseCommand;
use Bundles\PasswordLoginBundle\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserVerifyCommand extends Command
{
    /**
     * @var UserManager
     */
    private $userManager;

    public function __construct(UserManager $userManager)
    {
        parent::__construct();

        $this->userManager = $userManager;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('user:verify')
            ->setDescription('Mark/unmark user\'s email as verified')
            ->addArgument('email', InputArgument::REQUIRED, 'User email');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('email');
        $user     = $this->userManager->findOneByUsername($username);

        if (is_null($user)) {
            $output->writeln("<error>User {$username} not found.</error>");

            return 1;
        }

        $user->setIsVerified(1 - $user->isVerified());
        $this->userManager->save($user);

        $status = $user->isVerified() ? '<question>verifed</question>' : '<error>unverified</error>';
        $output->writeln("User <info>{$username}</info> is now: {$status}.");

        return 0;
    }
}
