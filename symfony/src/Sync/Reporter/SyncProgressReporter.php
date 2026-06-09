<?php

namespace App\Sync\Reporter;

/**
 * Optional UI feedback channel for the orchestrator. The default
 * NullSyncProgressReporter is a no-op (cron / GCT path). The
 * ConsoleSyncProgressReporter wraps Symfony's ProgressBar for the
 * sync:data --dir CLI dry-run.
 */
interface SyncProgressReporter
{
    /**
     * Emit a one-line status message (phase transitions, totals).
     */
    public function info(string $message) : void;

    /**
     * Start a determinate progress bar with a known total.
     * Any previously started bar is automatically finished first.
     */
    public function startBar(string $label, int $total) : void;

    public function advanceBar(int $step = 1) : void;

    public function finishBar() : void;
}
