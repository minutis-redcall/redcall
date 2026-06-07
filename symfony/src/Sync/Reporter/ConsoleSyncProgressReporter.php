<?php

namespace App\Sync\Reporter;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsoleSyncProgressReporter implements SyncProgressReporter
{
    private const MEMORY_REFRESH_EVERY = 50;

    private OutputInterface $output;
    private ?ProgressBar $bar = null;
    private int $stepsSinceMemoryRefresh = 0;

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
        $this->bar->setMessage($this->memoryFootprint());
        $this->bar->setRedrawFrequency(max(1, (int) ($total / 100)));
        $this->stepsSinceMemoryRefresh = 0;
        $this->bar->start();
    }

    public function advanceBar(int $step = 1) : void
    {
        if ($this->bar === null) {
            return;
        }
        $this->bar->advance($step);
        $this->stepsSinceMemoryRefresh += $step;
        if ($this->stepsSinceMemoryRefresh >= self::MEMORY_REFRESH_EVERY) {
            $this->bar->setMessage($this->memoryFootprint());
            $this->stepsSinceMemoryRefresh = 0;
        }
    }

    public function finishBar() : void
    {
        if ($this->bar !== null) {
            $this->bar->setMessage($this->memoryFootprint());
            $this->bar->finish();
            $this->output->writeln('');
            $this->bar = null;
        }
    }

    private function memoryFootprint() : string
    {
        $bytes = memory_get_usage(true);
        $units = ['B', 'KB', 'MB', 'GB'];
        $i     = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return sprintf('mem %.1f %s', $bytes, $units[$i]);
    }
}
