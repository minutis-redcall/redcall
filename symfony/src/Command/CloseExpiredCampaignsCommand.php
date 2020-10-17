<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Entity\Campaign;
use App\Manager\CampaignManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CloseExpiredCampaignsCommand extends BaseCommand
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
            ->setName('campaign:expired')
            ->setDescription('Close campaigns inactive since X days')
            ->addArgument('days', InputArgument::OPTIONAL, 'Number of days before closing an inactive campaign', 7);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $campaigns = $this->campaignManager->findInactiveCampaignsSince(
            $input->getArgument('days')
        );

        foreach ($campaigns as $campaign) {
            /** @var Campaign $campaign */
            $output->writeln(sprintf('Closing #%d', $campaign->getId()));
            $this->campaignManager->closeCampaign($campaign);
        }

        return 0;
    }
}