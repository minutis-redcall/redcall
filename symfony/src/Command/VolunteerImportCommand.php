<?php

namespace App\Command;

use App\Services\VolunteerImporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VolunteerImportCommand extends Command
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
            ->setName('volunteer:import')
            ->setDescription('Import all volunteers from an organization')
            ->addArgument('organization-code', InputArgument::REQUIRED, 'Organization code');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->importer->importOrganizationVolunteers($input->getArgument('organization-code'));
    }
}
