<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Manager\CampaignManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReportStructureCommand extends BaseCommand
{
    /**
     * @var CampaignManager
     */
    private $campaignManager;

    public function __construct(CampaignManager $campaignManager)
    {
        parent::__construct();

        $this->campaignManager = $campaignManager;
    }

    protected function configure()
    {
        $this
            ->setName('report:structure')
            ->addArgument('from', InputArgument::OPTIONAL, 'Date from', '2020-01-01')
            ->addArgument('to', InputArgument::OPTIONAL, 'Date to', '2021-01-01')
            ->addOption('structure', null, InputOption::VALUE_REQUIRED, 'Restrict to a given structure and its child')
            ->setDescription('Create a report');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        return 0;
    }
}