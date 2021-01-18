<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Manager\CampaignManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCampaignCommand extends BaseCommand
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
            ->setName('clear:campaign')
            ->setDescription('Close expired campaigns');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->campaignManager->closeExpiredCampaigns();

        return 0;
    }
}