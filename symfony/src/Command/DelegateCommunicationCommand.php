<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Communication\Processor\QueueProcessor;
use App\Manager\CommunicationManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DelegateCommunicationCommand extends BaseCommand
{
    /**
     * @var CommunicationManager
     */
    private $communicationManager;

    /**
     * @var QueueProcessor
     */
    private $processor;

    public function __construct(CommunicationManager $communicationManager, QueueProcessor $processor)
    {
        parent::__construct();

        $this->communicationManager = $communicationManager;
        $this->processor = $processor;
    }

    protected function configure()
    {
        $this
            ->setName('communication:task')
            ->setDescription('Enqueue at Google messages of the given communication')
            ->addArgument('communication-id', InputArgument::REQUIRED, 'Communication ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        date_default_timezone_set('Europe/Paris');

        $communicationId = $input->getArgument('communication-id');
        $communication = $this->communicationManager->find($communicationId);
        if (!$communication) {
            $output->writeln(sprintf('<error>Communication "%d" not found.</error>', $communicationId));

            return 1;
        }

        $this->processor->enqueue($communication);

        return 0;
    }
}
