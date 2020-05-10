<?php

namespace Bundles\PasswordLoginBundle\Command;

use Bundles\PasswordLoginBundle\Base\BaseCommand;
use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Bundles\PasswordLoginBundle\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserListCommand extends Command
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
            ->setName('user:list')
            ->setDescription('List all users stored on database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        (new Table($output))
            ->setHeaders(['User Email', 'Verified', 'Trusted', 'Admin'])
            ->setRows(array_map(function (AbstractUser $user) {
                return [
                    $user->getUsername(),
                    var_export($user->isVerified(), true),
                    var_export($user->isTrusted(), true),
                    var_export($user->isAdmin(), true),
                ];
            }, $this->userManager->findAll()))->render();

        return 0;
    }
}
