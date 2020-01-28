<?php

namespace Bundles\PasswordLoginBundle\Command;

use Bundles\PasswordLoginBundle\Base\BaseCommand;
use Bundles\PasswordLoginBundle\Entity\User;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserListCommand extends BaseCommand
{
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
            ->setRows(array_map(function (User $user) {
                return [
                    $user->getUsername(),
                    var_export($user->isVerified(), true),
                    var_export($user->isTrusted(), true),
                    var_export($user->isAdmin(), true),
                ];
            }, $this->getManager(User::class)->findAll()))->render();

        return 0;
    }
}
