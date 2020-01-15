<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Manager\RefreshManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshCommand extends BaseCommand
{
    /**
     * @var RefreshManager
     */
    private $refreshManager;

    public function __construct(RefreshManager $importManager)
    {
        parent::__construct();

        $this->refreshManager = $importManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('refresh')
            ->setDescription('Refresh all structures and volunteers based on Pegass cache');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        date_default_timezone_set('UTC');

        $this->refreshManager->refresh();
    }
}