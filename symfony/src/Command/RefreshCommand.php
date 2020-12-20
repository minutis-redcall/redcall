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

    /**
     * @var PegassManager
     */
    private $pegassManager;

    public function __construct(RefreshManager $refreshManager, PegassManager $pegassManager)
    {
        parent::__construct();

        $this->refreshManager = $refreshManager;
        $this->pegassManager  = $pegassManager;
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

        //        $this->refreshManager->refreshVolunteer(
        //            $this->pegassManager->getEntity(\Bundles\PegassCrawlerBundle\Entity\Pegass::TYPE_VOLUNTEER, '00000342302R'),
        //            true
        //        );
        //        return 0;

        // Refresh everything
        $this->refreshManager->refresh(
            $input->getOption('force')
        );

        return 0;
    }
}