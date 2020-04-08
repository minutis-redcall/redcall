<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Communication\Sender;
use App\Manager\CommunicationManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendCommunicationCommand extends BaseCommand
{
    /**
     * @var CommunicationManager
     */
    private $communicationManager;

    /**
     * @var Sender
     */
    private $sender;

    /**
     * @param CommunicationManager $communicationManager
     * @param Sender               $sender
     */
    public function __construct(CommunicationManager $communicationManager, Sender $sender)
    {
        parent::__construct();
        $this->communicationManager = $communicationManager;
        $this->sender = $sender;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('send:communication')
            ->setDescription('Run a "send:message" process on all messages of the given communication')
            ->addArgument('communication-id', InputArgument::REQUIRED, 'Communication ID')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Send messages even though they have been sent already.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        date_default_timezone_set('Europe/Paris');

        $communicationId = $input->getArgument('communication-id');
        $communication = $this->communicationManager->find($communicationId);
        if (!$communication) {
            $output->writeln(sprintf('<error>Communication "%d" not found.</error>', $communicationId));

            return 1;
        }

        date_default_timezone_set('Europe/Paris');

        $this->sender->sendCommunication($communication, $input->getOption('force'));
    }
}
