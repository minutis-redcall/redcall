<?php

namespace App\Command;

use App\Task\StartDataSyncTask;
use Bundles\GoogleTaskBundle\Service\TaskSender;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sync:data', description: 'Daily sync of volunteers and structures from the CSV bucket')]
class DataSyncCommand extends Command
{
    private TaskSender $taskSender;

    public function __construct(TaskSender $taskSender)
    {
        parent::__construct();
        $this->taskSender = $taskSender;
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $syncedAt = new \DateTimeImmutable();
        $this->taskSender->fire(StartDataSyncTask::class, [
            'syncedAt' => $syncedAt->format(\DateTimeInterface::ATOM),
        ]);

        $output->writeln(sprintf('<info>Fired StartDataSyncTask (syncedAt=%s)</info>', $syncedAt->format('c')));

        return Command::SUCCESS;
    }
}
