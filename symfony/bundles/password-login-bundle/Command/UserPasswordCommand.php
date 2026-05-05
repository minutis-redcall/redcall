<?php

namespace Bundles\PasswordLoginBundle\Command;

use App\Entity\User;
use Bundles\PasswordLoginBundle\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class UserPasswordCommand extends Command
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var PasswordHasherFactoryInterface
     */
    private $hasherFactory;

    public function __construct(UserManager $userManager, PasswordHasherFactoryInterface $hasherFactory)
    {
        parent::__construct();

        $this->userManager   = $userManager;
        $this->hasherFactory = $hasherFactory;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('user:password')
            ->setDescription('Update user\'s password')
            ->addArgument('email', InputArgument::REQUIRED, 'User\'s email')
            ->addArgument('password', InputArgument::REQUIRED, 'New password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('email');
        $password = $input->getArgument('password');

        $user = $this->userManager->findOneByUsername($username);
        if (is_null($user)) {
            $output->writeln("<error>User {$username} not found.</error>");

            return 1;
        }

        $hasher = $this->hasherFactory->getPasswordHasher(User::class);
        $user->setPassword($hasher->hash($password));
        $this->userManager->save($user);

        $output->writeln("User <info>{$username}</info>'s password updated.");

        return 0;
    }
}
