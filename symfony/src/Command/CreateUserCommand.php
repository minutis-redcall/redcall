<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Manager\UserManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateUserCommand extends BaseCommand
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

    /**
     * {@inheritdoc}
     */
    protected function configure() : void
    {
        $this
            ->setName('user:create')
            ->setDescription('Create users based on volunteer\'s external id')
            ->addArgument('external-id', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'External IDs from which to create a new user');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        foreach ($input->getArgument('external-id') as $externalId) {
            try {
                $this->userManager->createUser($externalId, null, 'CLI: user:create');
            } catch (\LogicException $e) {
                $output->writeln(sprintf('KO %s: %s', $externalId, $e->getMessage()));
                continue;
            }

            $output->writeln(sprintf('OK %s: user created', $externalId));
        }

        return 0;
    }
}
