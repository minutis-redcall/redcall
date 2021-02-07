<?php

namespace App\Command;

use App\Manager\VolunteerSessionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearSpaceCommand extends Command
{
    /**
     * @var VolunteerSessionManager
     */
    private $volunteerSessionManager;

    /**
     * @param VolunteerSessionManager $volunteerSessionManager
     */
    public function __construct(VolunteerSessionManager $volunteerSessionManager)
    {
        parent::__construct();

        $this->volunteerSessionManager = $volunteerSessionManager;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('clear:space')
            ->setDescription('Clears up old space sessions');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->volunteerSessionManager->clearExpired();

        return 0;
    }
}
