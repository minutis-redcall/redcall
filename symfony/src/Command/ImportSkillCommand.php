<?php

namespace App\Command;

use App\Services\VolunteerImporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportSkillCommand  extends Command
{
    /**
     * @var VolunteerImporter
     */
    private $importer;

    /**
     * @param VolunteerImporter $importer
     */
    public function __construct(VolunteerImporter $importer)
    {
        parent::__construct();

        $this->importer = $importer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('import:skill')
            ->setDescription('Import or update volunteer skills')
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Sleep time in second between 2 volunteers', 1)
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of volunteers to update', 15);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->importer->importVolunteersSkills(
            $input->getOption('sleep'),
            $input->getOption('limit')
        );
    }
}
