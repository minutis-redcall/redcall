<?php

namespace App\Command;

use App\Base\BaseCommand;
use App\Entity\Communication;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class SendCommunicationCommand extends BaseCommand
{
    const PAUSE = 100000; // 100 000 microseconds

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('send:communication')
            ->setDescription('Run a "send:message" process on all messages of the given communication')
            ->addArgument('communication-id', InputArgument::REQUIRED, 'Communication ID');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $communicationId = $input->getArgument('communication-id');

        /* @var Communication $communication */
        $communication = $this->getManager(Communication::class)->find($communicationId);

        if (!$communication) {
            $output->writeln(sprintf('<error>Communication "%d" not found.</error>', $communicationId));

            return 1;
        }

        $console = sprintf('%s/../bin/console', $this->get('kernel')->getRootDir());

        $processes = [];
        foreach ($communication->getMessages() as $message) {
            $process = Process::fromShellCommandline(sprintf('%s send:message %d', $console, $message->getId()));
            $process->start();
            $processes[$message->getId()] = $process;
            usleep(self::PAUSE);
        }

        $timeouts = [];
        foreach ($processes as $messageId => $process) {
            /** @var Process $process */
            try {
                $process->wait();
            } catch (ProcessTimedOutException $e) {
                $output->writeln('<error>Timeout on %s</error>', $process->getCommandLine());
                $timeouts[] = $messageId;
            }
        }

        if ($timeouts) {
            throw new \RuntimeException(sprintf('Process(es) timed out on message ids: %s', implode(', ', $timeouts)));
        }
    }
}
