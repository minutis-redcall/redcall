<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Manager\RefreshManager;
use Bundles\PegassCrawlerBundle\Manager\PegassManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshCommand extends BaseCommand
{
    /**
     * @var RefreshManager
     */
    private $refreshManager;

    public function __construct(RefreshManager $refreshManager, PegassManager $pegassManager)
    {
        parent::__construct();

        $this->refreshManager = $refreshManager;
    }

    protected function configure()
    {
        $this
            ->setName('refresh')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Forces refreshing even though data are up to date')
            ->setDescription('Refresh structures and volunteers based on Pegass cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        date_default_timezone_set('UTC');

        // Refresh everything
        $this->refreshManager->refresh(
            $input->getOption('force')
        );

        return 0;
    }
}