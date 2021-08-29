<?php

namespace App\Contract;

interface LockableInterface
{
    public function isLocked() : ?bool;

    public function getDisplayName() : string;
}