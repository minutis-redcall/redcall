<?php

namespace App\Command;

use App\Sync\DataSyncOrchestrator;
use App\Sync\Reporter\ConsoleSyncProgressReporter;
use App\Sync\Source\LocalCsvSource;
use App\Task\StartDataSyncTask;
use Bundles\GoogleTaskBundle\Service\TaskSender;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
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
        // In prod the process here just dispatches StartDataSyncTask to GCT
        // and exits — the workhorse runs inside each Cloud Task with its own
        // memory budget. In dev TaskSender inlines, so the orchestrator's
        // 80k volunteer DTOs + every chunk import end up in this same PHP
        // process and would easily blow past 128 MB. Lift the cap.
        ini_set('memory_limit', '-1');

        $syncedAt = new \DateTimeImmutable();
        $dir      = $input->getOption('dir');

        if ($dir) {
            $dir = rtrim((string) $dir, DIRECTORY_SEPARATOR);
            if (!is_dir($dir)) {
                $output->writeln(sprintf('<error>Directory does not exist: %s</error>', $dir));

                return Command::FAILURE;
            }

            // The full prod export carries ~80k volunteers + ~700k training
            // rows. Doctrine's identity map alone tops the default 2 GB even
            // with periodic em->clear() in the orchestrator. We're in a dev
            // one-shot here — drop the cap.
            ini_set('memory_limit', '-1');

            // Phase labels + progress bars are emitted by the reporter on
            // stdout. The logger is mapped to verbose-only so its routine
            // "X / Y" lines don't fight with the progress bar redraws.
            $this->orchestrator->setLogger(new ConsoleLogger($output, [
                \Psr\Log\LogLevel::INFO => OutputInterface::VERBOSITY_VERBOSE,
            ]));
            $this->orchestrator->setProgressReporter(new ConsoleSyncProgressReporter($output));

            // In dev env, Doctrine's profiling captures every executed SQL
            // statement in a DebugDataHolder. Over a full sync that's 400k+
            // entries / ~1 GB of RAM. Pass the holder to the orchestrator
            // so it can reset() it after every chunk.
            $container = $this->getApplication()->getKernel()->getContainer();
            if ($container->has(DebugDataHolder::class)) {
                $holder = $container->get(DebugDataHolder::class);
                if ($holder instanceof DebugDataHolder) {
                    $this->orchestrator->setDoctrineDebugDataHolder($holder);
                }
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
