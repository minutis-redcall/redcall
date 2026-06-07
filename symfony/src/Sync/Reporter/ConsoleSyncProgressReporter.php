<?php

namespace App\Sync\Reporter;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsoleSyncProgressReporter implements SyncProgressReporter
{
    private OutputInterface $output;
    private ?ProgressBar $bar = null;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        ProgressBar::setFormatDefinition('sync', ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %message%');
    }

    public function info(string $message) : void
    {
        $this->finishBar();
        $this->output->writeln(sprintf('<info>%s</info>', $message));
    }

    public function startBar(string $label, int $total) : void
    {
        $this->finishBar();

        $this->output->writeln(sprintf('<info>%s</info>', $label));

        $this->bar = new ProgressBar($this->output, $total);
        $this->bar->setFormat('sync');
        $this->bar->setMessage('');
        $this->bar->setRedrawFrequency(max(1, (int) ($total / 100)));
        $this->bar->start();
    }

    public function advanceBar(int $step = 1) : void
    {
        $this->bar?->advance($step);
    }

    public function finishBar() : void
    {
        if ($this->bar !== null) {
            $this->bar->finish();
            $this->output->writeln('');
            $this->bar = null;
        }
    }
}
