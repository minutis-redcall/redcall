<?php

namespace App\Command;

use App\Manager\PegassManager;
use App\Manager\VolunteerManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearVolunteerCommand extends Command
{
    protected static $defaultName        = 'clear:volunteer';
    protected static $defaultDescription = 'Anonymizes volunteers that are disabled on pegass database';

    /**
     * @var PegassManager
     */
    protected $pegassManager;

    /**
     * @var VolunteerManager
     */
    protected $volunteerManager;

    public function __construct(PegassManager $pegassManager, VolunteerManager $volunteerManager)
    {
        parent::__construct();

        $this->pegassManager    = $pegassManager;
        $this->volunteerManager = $volunteerManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $volunteerIds = $this->volunteerManager->findVolunteersToAnonymize();
        foreach ($volunteerIds as $volunteerId) {
            $volunteer = $this->volunteerManager->find($volunteerId);
            $this->volunteerManager->anonymize($volunteer);
            $output->writeln($volunteerId);
        }

        return Command::SUCCESS;
    }
}