<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Services\Pegass;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PegassOrganizationsCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pegass:organizations')
            ->setDescription('List Pegass organizations in a department')
            ->addArgument('department', InputArgument::REQUIRED, 'Department code, with trailing zeros if any');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $department = $input->getArgument('department');

        $organizations = $this->get(Pegass::class)->listOrganizations($department);

        $table = new Table($output);
        $table->setHeaderTitle(sprintf('Organizations in department #%s', $department));
        $table->setHeaders(['ID', 'Type', 'Name']);
        $table->addRows($organizations);

        $table->render();
    }
}