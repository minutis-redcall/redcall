<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Services\Pegass;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PegassDepartmentsCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pegass:departments')
            ->setDescription('List Pegass departments');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $departments = $this->get(Pegass::class)->listDepartments();

        $table = new Table($output);
        $table->setHeaders(['ID', 'Name']);
        $table->addRows($departments);

        $table->render();
    }
}