<?php

namespace App\Command;

use App\Sync\DataSyncOrchestrator;
use App\Sync\Source\LocalCsvSource;
use App\Task\StartDataSyncTask;
use Bundles\GoogleTaskBundle\Service\TaskSender;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sync:data', description: 'Daily sync of volunteers and structures from the CSV bucket')]
class DataSyncCommand extends Command
{
    private TaskSender $taskSender;
    private DataSyncOrchestrator $orchestrator;

    public function __construct(TaskSender $taskSender, DataSyncOrchestrator $orchestrator)
    {
        parent::__construct();
        $this->taskSender   = $taskSender;
        $this->orchestrator = $orchestrator;
    }

    protected function configure() : void
    {
        $this->addOption(
            'dir',
            null,
            InputOption::VALUE_REQUIRED,
            'Run the sync inline against a local CSV directory (bypasses GCS and Cloud Tasks). '
            .'Use to dry-run a snapshot of the DSI export.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $syncedAt = new \DateTimeImmutable();
        $dir      = $input->getOption('dir');

        if ($dir) {
            $dir = rtrim((string) $dir, DIRECTORY_SEPARATOR);
            if (!is_dir($dir)) {
                $output->writeln(sprintf('<error>Directory does not exist: %s</error>', $dir));

                return Command::FAILURE;
            }

            $output->writeln(sprintf('<info>Running sync inline from %s (syncedAt=%s)</info>', $dir, $syncedAt->format('c')));

            $this->orchestrator->runInline($syncedAt, new LocalCsvSource($dir));

            $output->writeln('<info>Done.</info>');

            return Command::SUCCESS;
        }

        $this->taskSender->fire(StartDataSyncTask::class, [
            'syncedAt' => $syncedAt->format(\DateTimeInterface::ATOM),
        ]);

        $output->writeln(sprintf('<info>Fired StartDataSyncTask (syncedAt=%s)</info>', $syncedAt->format('c')));

        return Command::SUCCESS;
    }
}
