<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Manager\PegassManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PegassCommand extends BaseCommand
{
    /**
     * @var PegassManager
     */
    private $pegassManager;

    public function __construct(PegassManager $pegassManager)
    {
        parent::__construct();

        $this->pegassManager = $pegassManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pegass')
            ->setDescription('Heat Pegass cache')
            ->addArgument('limit', InputArgument::OPTIONAL, 'Number of entities to refresh', 3)
            ->addOption('from-cache', null, InputOption::VALUE_NONE, 'Process content from cache instead of fetching Pegass');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        date_default_timezone_set('UTC');

        $this->pegassManager->heat(
            $input->getArgument('limit'),
            $input->getOption('from-cache')
        );
    }
}