<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Services\Pegass;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PegassVolunteersCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pegass:volunteers')
            ->setDescription('List volunteers in an organization')
            ->addArgument('organization', InputArgument::REQUIRED, 'Organization code');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $organization = $input->getArgument('organization');

        $volunteers = $this->get(Pegass::class)->listVolunteers($organization);

        $table = new Table($output);
        $table->setHeaderTitle(sprintf('Volunteers in organization #%s', $organization));
        $table->setHeaders(['ID', 'Firstname', 'Lastname', 'Phone', 'Email', 'Enabled', 'Minor']);
        /* @var \App\Entity\Volunteer $volunteer */
        foreach ($volunteers as $volunteer) {
            $table->addRow([
                $volunteer->getNivol(),
                $volunteer->getFirstName(),
                $volunteer->getLastName(),
                $volunteer->getPhoneNumber(),
                $volunteer->getEmail(),
                $volunteer->isEnabled(),
                $volunteer->isMinor(),
            ]);
        }

        $table->render();
    }
}