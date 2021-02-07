<?php

namespace App\Command;

use App\Manager\ExpirableManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearExpirableCommand extends Command
{
    /**
     * @var ExpirableManager
     */
    private $expirableManager;

    public function __construct(ExpirableManager $expirableManager)
    {
        parent::__construct();

        $this->expirableManager = $expirableManager;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('clear:expirable')
            ->setDescription('Clears up old expirable entities');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->expirableManager->clearExpired();

        return 0;
    }
}