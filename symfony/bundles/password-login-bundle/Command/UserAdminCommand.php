<?php

namespace Bundles\PasswordLoginBundle\Command;

use App\Entity\User;
use App\Manager\UserAuditLogManager;
use Bundles\PasswordLoginBundle\Base\BaseCommand;
use Bundles\PasswordLoginBundle\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserAdminCommand extends Command
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
            ->setName('user:admin')
            ->setDescription('Set/Unset user as admin')
            ->addArgument('email', InputArgument::REQUIRED, 'User email');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $username = $input->getArgument('email');
        $user     = $this->userManager->findOneByUsername($username);

        if (is_null($user)) {
            $output->writeln("<error>User {$username} not found.</error>");

            return 1;
        }

        $old = $user instanceof User ? $this->userAuditLogManager->buildSnapshot($user) : null;
        $user->setIsAdmin(1 - $user->isAdmin());
        $this->userManager->save($user);
        if ($user instanceof User && null !== $old) {
            $this->userAuditLogManager->logUpdated(null, 'CLI: user:admin', $user, $old);
        }

        $status = $user->isAdmin() ? '<question>admin</question>' : '<error>simple user</error>';
        $output->writeln("User <info>{$username}</info> is now: {$status}.");

        return 0;
    }
}
