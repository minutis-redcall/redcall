<?php

namespace App\Sync\Reporter;

final class NullSyncProgressReporter implements SyncProgressReporter
{
    public function info(string $message) : void
    {
    }

    public function startBar(string $label, int $total) : void
    {
    }

    public function advanceBar(int $step = 1) : void
    {
    }

    public function finishBar() : void
    {
    }
}
