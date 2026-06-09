<?php

namespace App\Command;

use App\Entity\User;
use App\Manager\UserAuditLogManager;
use App\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserRootCommand extends Command
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var UserAuditLogManager
     */
    private $userAuditLogManager;

    public function __construct(UserManager $userManager, UserAuditLogManager $userAuditLogManager)
    {
        parent::__construct();

        $this->userManager         = $userManager;
        $this->userAuditLogManager = $userAuditLogManager;
    }

    protected function configure() : void
    {
        parent::configure();

        $this
            ->setName('user:root')
            ->setDescription('Mark/unmark user as root')
            ->addArgument('email', InputArgument::REQUIRED, 'User email');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $username = $input->getArgument('email');

        /** @var User $user */
        $user = $this->userManager->findOneByUsername($username);

        if (is_null($user)) {
            $output->writeln("<error>User {$username} not found.</error>");

            return 1;
        }

        $old = $this->userAuditLogManager->buildSnapshot($user);
        $user->setIsRoot(1 - $user->isRoot());
        $this->userManager->save($user);
        $this->userAuditLogManager->logUpdated(null, 'CLI: user:root', $user, $old);

        $status = $user->isRoot() ? '<question>root</question>' : '<error>not root</error>';
        $output->writeln("User <info>{$username}</info> is now: {$status}.");

        return 0;
    }
}
